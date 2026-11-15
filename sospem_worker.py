import os
import sys
import re
import json
import time
import random
import hashlib
import threading
import urllib.parse
from datetime import datetime
from itertools import islice
from collections import Counter
from typing import List, Literal, Optional

target_dir = os.path.abspath("./sospem_packages")
if os.path.exists(target_dir) and target_dir not in sys.path:
    sys.path.insert(0, target_dir)

os.environ["HF_HUB_DISABLE_XET"] = "1"

import torch
import requests
import yt_dlp
import feedparser
from pydantic import BaseModel, Field
from fastapi import FastAPI, BackgroundTasks, Header, HTTPException, Request
from transformers import AutoTokenizer, AutoModelForCausalLM
from youtube_comment_downloader import YoutubeCommentDownloader, SORT_BY_RECENT
import uvicorn

# ==============================================================================
# 1. KONFIGURASI PRODUCTION SOSPEM
# ==============================================================================
BASE_URL = os.environ.get("SOSPEM_BASE_URL", "https://sospem.pubhe.cloud")
SOSPEM_API_KEY = os.environ.get("SOSPEM_API_KEY", "")
WEBHOOK_SECRET = os.environ.get("SCRAPER_WEBHOOK_SECRET", "sospem-secret-key-2026")

HEADERS_LARAVEL = {"Accept": "application/json"}
if SOSPEM_API_KEY:
    HEADERS_LARAVEL["X-Sospem-Key"] = SOSPEM_API_KEY

MODEL_ID = "Qwen/Qwen2.5-1.5B-Instruct"
FILE_JSON = "stream_analisis_sospem.json"
FILE_IDS = "komentar_terproses_ids.json"

PETA_SLOT_LARAVEL = {"meta": "meta", "x": "x", "tiktok": "tiktok", "youtube": "tiktok", "news": "meta"}
PETA_SENTIMEN_LARAVEL = {"Positif": "positive", "Negatif": "negative", "Netral": "neutral"}

# ==============================================================================
# 2. INISIALISASI MODEL AI (DIBAGI AMAN ANTARA SCRAPER & CHATBOT VIA MODEL_LOCK)
# ==============================================================================
device = "mps" if torch.backends.mps.is_available() else ("cuda" if torch.cuda.is_available() else "cpu")
dtype = torch.float16 if device in ("cuda", "mps") else torch.bfloat16
MODEL_LOCK = threading.Lock()
SCRAPE_RUNNING = threading.Lock()

print(f"[INIT] Memuat tokenizer & model {MODEL_ID} di {device.upper()}...")
tokenizer = AutoTokenizer.from_pretrained(MODEL_ID)
model = AutoModelForCausalLM.from_pretrained(
    MODEL_ID,
    dtype=dtype,
    low_cpu_mem_usage=True,
).to(device)
model.eval()
print(f"[SIAP] Model AI {MODEL_ID} aktif di {device.upper()}!")

# ==============================================================================
# 3. SKEMA & FUNGSI ANALISIS SENTIMEN + DETEKSI BOT
# ==============================================================================
class SentimenLembaga(BaseModel):
    lembaga: str
    sentimen: Literal["Positif", "Negatif", "Netral"]
    alasan: str

class HasilAnalisisSospem(BaseModel):
    username: str
    relevan_pemerintahan: bool
    sentimen_publik: Literal["Positif", "Negatif", "Netral"]
    sentimen_per_lembaga: List[SentimenLembaga]
    indikasi_bot_persen: float = Field(ge=0, le=100)
    alasan_indikasi_bot: str

def tentukan_sentimen_dominan(daftar_lembaga: list) -> str:
    if not daftar_lembaga:
        return "Netral"
    skor = [x["sentimen"] if isinstance(x, dict) else x.sentimen for x in daftar_lembaga]
    if skor.count("Negatif") > skor.count("Positif"):
        return "Negatif"
    if skor.count("Positif") > skor.count("Negatif"):
        return "Positif"
    if "Negatif" in skor:
        return "Negatif"
    if "Positif" in skor:
        return "Positif"
    return "Netral"

def parse_hasil_sospem_aman(teks_output: str, username: str) -> HasilAnalisisSospem:
    match = re.search(r"\{.*\}", teks_output, re.DOTALL)
    if match:
        try:
            data = json.loads(match.group(0))
            sentimen_valid = []
            for item in data.get("sentimen_per_lembaga", []):
                if isinstance(item, dict) and item.get("lembaga"):
                    s = str(item.get("sentimen", "Netral")).strip().capitalize()
                    if s not in ("Positif", "Negatif", "Netral"):
                        s = "Netral"
                    sentimen_valid.append({
                        "lembaga": str(item["lembaga"]).strip(),
                        "sentimen": s,
                        "alasan": str(item.get("alasan", "Opini terkait berita")).strip()
                    })
            sent_pub = str(data.get("sentimen_publik", "")).strip().capitalize()
            if sent_pub not in ("Positif", "Negatif", "Netral"):
                sent_pub = tentukan_sentimen_dominan(sentimen_valid)
            skor_bot = max(0.0, min(100.0, float(data.get("indikasi_bot_persen", data.get("skor_bot", 20.0)))))
            alasan_bot = str(data.get("alasan_indikasi_bot", data.get("indikasi_bot", "Analisis standar"))).strip()
            return HasilAnalisisSospem(
                username=username,
                relevan_pemerintahan=bool(data.get("relevan_pemerintahan", False)),
                sentimen_publik=sent_pub,
                sentimen_per_lembaga=sentimen_valid,
                indikasi_bot_persen=skor_bot,
                alasan_indikasi_bot=alasan_bot
            )
        except Exception:
            pass
    return HasilAnalisisSospem(
        username=username, relevan_pemerintahan=False, sentimen_publik="Netral",
        sentimen_per_lembaga=[], indikasi_bot_persen=20.0, alasan_indikasi_bot="Fallback JSON"
    )

def analisis_komentar_kontekstual(username: str, teks_komentar: str, likes: int, konteks_berita: str) -> HasilAnalisisSospem:
    teks_komentar = (teks_komentar or "")[:500]
    teks_bersih = re.sub(r"@\w+", "", teks_komentar).strip()
    if not re.search(r"[a-zA-Z]{2,}", teks_bersih):
        return HasilAnalisisSospem(
            username=username, relevan_pemerintahan=False, sentimen_publik="Netral",
            sentimen_per_lembaga=[], indikasi_bot_persen=70.0, alasan_indikasi_bot="Hanya emoji/simbol tanpa teks"
        )
    sys_msg = (
        "Anda adalah analis intelijen opini publik SOSPEM Indonesia. "
        "Komentar ini menanggapi BERITA PEMERINTAHAN pada Konteks Berita. "
        "Jika komentar hanya basa-basi, spam, atau tidak menyangkut berita/lembaga, set relevan_pemerintahan = false dan sentimen_per_lembaga = []. "
        "Jika komentar berisi opini, kritik, dukungan, tuntutan, atau sindiran terhadap isi berita, "
        "set relevan_pemerintahan = true, tentukan sentimen_publik (Positif, Negatif, atau Netral), "
        "dan sebutkan nama lembaga negara/pejabat dari Konteks Berita beserta sentimennya secara terpisah di sentimen_per_lembaga. "
        "Keluarkan HANYA objek JSON valid."
    )
    messages = [
        {"role": "system", "content": sys_msg},
        {"role": "user", "content": "Konteks Berita: Pemerintah dan DPR membahas kenaikan PPN 12%.\nKomentar dari @agus_88 (3 likes): \"Rakyat kecil makin susah kalau naik terus\""},
        {"role": "assistant", "content": json.dumps({
            "username": "agus_88", "relevan_pemerintahan": True, "sentimen_publik": "Negatif",
            "sentimen_per_lembaga": [
                {"lembaga": "Pemerintah Pusat", "sentimen": "Negatif", "alasan": "Mengeluhkan beban kenaikan tarif"},
                {"lembaga": "DPR RI", "sentimen": "Negatif", "alasan": "Menolak wacana kenaikan"}
            ],
            "indikasi_bot_persen": 20.0, "alasan_indikasi_bot": "Keluhan organik warga"
        })},
        {"role": "user", "content": f"Konteks Berita: {konteks_berita}\nKomentar dari @{username} ({likes} likes): \"{teks_komentar}\""}
    ]
    text_in = tokenizer.apply_chat_template(messages, tokenize=False, add_generation_prompt=True)
    inputs = tokenizer([text_in], return_tensors="pt").to(model.device)
    with MODEL_LOCK, torch.no_grad():
        gen = model.generate(**inputs, max_new_tokens=350, do_sample=False)
    raw = tokenizer.decode(gen[0][len(inputs.input_ids[0]):], skip_special_tokens=True)
    return parse_hasil_sospem_aman(raw, username)

# ==============================================================================
# 4. ENGINE SCRAPER ON-DEMAND (BERDASARKAN TOPIC & LIMIT DARI DASHBOARD)
# ==============================================================================
yt_downloader = YoutubeCommentDownloader()

def parse_likes_aman(val) -> int:
    if isinstance(val, (int, float)):
        return int(val)
    s = str(val or "0").strip().upper()
    try:
        if "K" in s or "RB" in s:
            return int(float(re.sub(r"[^0-9.]", "", s.replace(",", ".")) or 0) * 1000)
        if "M" in s or "JT" in s:
            return int(float(re.sub(r"[^0-9.]", "", s.replace(",", ".")) or 0) * 1000000)
        return int(re.sub(r"[^0-9]", "", s) or 0)
    except Exception:
        return 0

def tarik_youtube_by_topic(topic: str, id_terproses: set, max_items: int) -> list:
    hasil_item = []
    ydl_opts = {"quiet": True, "extract_flat": True, "skip_download": True}
    query = f"berita terbaru {topic} indonesia" if topic else "berita terbaru presiden prabowo kebijakan pemerintah"
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(f"ytsearch3:{query}", download=False)
            videos = [e for e in (info.get("entries") or []) if e and e.get("id") and e.get("title")]

        for v in videos:
            if len(hasil_item) >= max_items:
                break
            v_url = f"https://www.youtube.com/watch?v={v['id']}"
            konteks = f"Video Berita {v.get('uploader') or 'Media'}: {v['title']}"
            try:
                gen = yt_downloader.get_comments_from_url(v_url, sort_by=SORT_BY_RECENT)
                for c in islice(gen, max_items):
                    if len(hasil_item) >= max_items:
                        break
                    teks = (c.get("text") or "").strip()
                    user = (c.get("author") or "warganet").lstrip("@")
                    cid = "vid_" + str(c.get("cid") or f"{v.get('id')}_{user}_{teks[:20]}")
                    if teks and cid not in id_terproses:
                        hasil_item.append({
                            "cid": cid, "platform": "youtube", "kanal": "YouTube Live Scraper",
                            "username": user, "text": teks,
                            "likes": parse_likes_aman(c.get("votes")),
                            "url": v_url, "judul_berita": v["title"], "konteks": konteks
                        })
            except Exception:
                continue
    except Exception as e:
        print(f"[YouTube Error] {e}")
    return hasil_item

def tarik_rss_by_topic(topic: str, id_terproses: set, max_items: int) -> list:
    hasil_item = []
    q = topic if topic else "Kebijakan Pemerintah DPR KPK"
    try:
        rss_url = f"https://news.google.com/rss/search?q={urllib.parse.quote(q)}&hl=id&gl=ID&ceid=ID:id"
        feed = feedparser.parse(rss_url)
        for entry in feed.entries[:max_items]:
            judul = (entry.get("title") or "").strip()
            link = entry.get("link") or ""
            sumber = entry.get("source", {}).get("title", "Media Nasional")
            cid = "rss_" + hashlib.md5((link or judul).encode("utf-8")).hexdigest()[:16]
            if judul and cid not in id_terproses:
                hasil_item.append({
                    "cid": cid, "platform": "news", "kanal": "Google News RSS Live",
                    "username": sumber.replace(" ", "_").lower(),
                    "text": judul, "likes": 0,
                    "url": link, "judul_berita": judul, "konteks": f"Tajuk Berita {sumber}: {judul}"
                })
    except Exception as e:
        print(f"[RSS Error] {e}")
    return hasil_item

def jalankan_scrape_on_demand(job_id: Optional[int], topic: str, limit: int, issue_slug: str = "auto"):
    with SCRAPE_RUNNING:
        print(f"\n[SCRAPE LIVE MULAI] Job #{job_id} | Topik: '{topic}' | Limit: {limit}")
        semua_hasil = json.load(open(FILE_JSON, encoding="utf-8")) if os.path.exists(FILE_JSON) else []
        id_terproses = set(json.load(open(FILE_IDS, encoding="utf-8"))) if os.path.exists(FILE_IDS) else set()

        kuota_yt = max(1, int(limit * 0.7))
        kuota_rss = max(1, limit - kuota_yt)

        antrean = []
        antrean.extend(tarik_youtube_by_topic(topic, id_terproses, kuota_yt))
        antrean.extend(tarik_rss_by_topic(topic, id_terproses, kuota_rss))
        antrean = list({it["cid"]: it for it in antrean}.values())[:limit]

        terkirim_sukses = 0
        try:
            for item in antrean:
                hasil_ai = analisis_komentar_kontekstual(
                    item["username"], item["text"], item["likes"], item["konteks"]
                )
                if item["platform"] == "news":
                    hasil_ai.relevan_pemerintahan = True
                    hasil_ai.indikasi_bot_persen = 0.0
                    hasil_ai.alasan_indikasi_bot = "Tajuk berita media nasional"

                daftar_lembaga_dict = [x.model_dump() for x in hasil_ai.sentimen_per_lembaga]
                plat_asli = item["platform"]
                plat_laravel = PETA_SLOT_LARAVEL.get(plat_asli, "meta")

                baris = {
                    "cid": item["cid"],
                    "Waktu": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                    "Platform SOSPEM": plat_asli,
                    "Kanal": item["kanal"],
                    "Sumber Berita": item["judul_berita"][:120],
                    "URL": item["url"],
                    "Akun": f"@{item['username']}",
                    "Komentar Asli": item["text"],
                    "Likes": item["likes"],
                    "Relevan": bool(hasil_ai.relevan_pemerintahan),
                    "Sentimen Publik": hasil_ai.sentimen_publik,
                    "Sentimen JSON": daftar_lembaga_dict,
                    "Skor Bot (%)": hasil_ai.indikasi_bot_persen,
                    "Catatan Forensik SOSPEM": hasil_ai.alasan_indikasi_bot,
                    "Terkirim": False,
                }

                if baris["Relevan"]:
                    payload = {
                        "issue_slug": issue_slug or "auto",
                        "topic": topic,
                        "platform": plat_laravel,
                        "platform_origin": plat_asli,
                        "source_channel": item["kanal"],
                        "external_id": item["cid"],
                        "author_name": item["username"].lstrip("@"),
                        "author_handle": f"@{item['username']}",
                        "content": item["text"],
                        "likes_count": item["likes"],
                        "source_url": item["url"],
                        "source_news": item["judul_berita"][:120],
                        "ai_sentiment": PETA_SENTIMEN_LARAVEL.get(hasil_ai.sentimen_publik, "neutral"),
                        "ai_sentiment_label": hasil_ai.sentimen_publik,
                        "ai_bot_score": hasil_ai.indikasi_bot_persen,
                        "ai_bot_reason": hasil_ai.alasan_indikasi_bot,
                        "ai_institutions": daftar_lembaga_dict,
                    }
                    try:
                        r = requests.post(
                            f"{BASE_URL.rstrip('/')}/api/ingest",
                            json=payload,
                            headers=HEADERS_LARAVEL,
                            timeout=15
                        )
                        if r.ok:
                            baris["Terkirim"] = True
                            terkirim_sukses += 1
                    except Exception as e_post:
                        print(f"[Ingest Error] {e_post}")

                semua_hasil.append(baris)
                id_terproses.add(item["cid"])

            with open(FILE_JSON, "w", encoding="utf-8") as f:
                json.dump(semua_hasil, f, indent=2, ensure_ascii=False)
            with open(FILE_IDS, "w", encoding="utf-8") as f:
                json.dump(list(id_terproses), f)

            if job_id:
                requests.post(
                    f"{BASE_URL.rstrip('/')}/api/scrape-jobs/{job_id}/done",
                    headers={**HEADERS_LARAVEL, "X-Webhook-Secret": WEBHOOK_SECRET},
                    json={"status": "completed", "scraped_count": terkirim_sukses},
                    timeout=15
                )
            print(f"[SCRAPE LIVE SELESAI] Job #{job_id} | Terkirim ke web: {terkirim_sukses}/{len(antrean)}")

        except Exception as e:
            print(f"[SCRAPE LIVE GAGAL] {e}")
            if job_id:
                try:
                    requests.post(
                        f"{BASE_URL.rstrip('/')}/api/scrape-jobs/{job_id}/done",
                        headers={**HEADERS_LARAVEL, "X-Webhook-Secret": WEBHOOK_SECRET},
                        json={"status": "failed", "error": str(e)},
                        timeout=15
                    )
                except Exception:
                    pass

# ==============================================================================
# 5. CHATBOT ENGINE (TETAP MELAYANI HALAMAN CHATBOT SOSPEM)
# ==============================================================================
def bersihkan_format_chat(teks: str) -> str:
    t = re.sub(r"\*\*(.*?)\*\*", r"\1", teks or "")
    t = re.sub(r"\*(.*?)\*", r"\1", t)
    t = re.sub(r"_(.*?)_", r"\1", t)
    t = re.sub(r"^#{1,6}\s*", "", t, flags=re.MULTILINE)
    return t.strip()

def jawab_chat_sospem(messages_input: list, prompt_tunggal: str = "") -> str:
    persona_sistem = (
        "Kamu adalah Asisten AI SOSPEM (Sistem Pemantauan Opini Publik & Jaringan Bot), "
        "sekaligus teman bicara yang hangat, cerdas, dan luwes. "
        "Jawab langsung dengan bahasa Indonesia yang natural tanpa simbol bintang (** atau *) dan tanpa tanda pagar (#)."
    )
    chat_history = [{"role": "system", "content": persona_sistem}]
    if messages_input:
        for m in messages_input:
            role = m.get("role", "user")
            content = (m.get("content") or "").strip()
            if content:
                if role == "system":
                    chat_history[0]["content"] += "\n\nKonteks Tambahan:\n" + content
                else:
                    chat_history.append({"role": role, "content": content})
    elif prompt_tunggal:
        chat_history.append({"role": "user", "content": prompt_tunggal.strip()})

    text_in = tokenizer.apply_chat_template(chat_history, tokenize=False, add_generation_prompt=True)
    inputs = tokenizer([text_in], return_tensors="pt").to(model.device)
    with MODEL_LOCK, torch.no_grad():
        gen = model.generate(**inputs, max_new_tokens=350, temperature=0.45, top_p=0.9, do_sample=True)
    raw = tokenizer.decode(gen[0][len(inputs.input_ids[0]):], skip_special_tokens=True)
    return bersihkan_format_chat(raw)

# ==============================================================================
# 6. FASTAPI ENDPOINTS (TRIGGER SCRAPE LIVE + CHATBOT OLLAMA COMPATIBLE)
# ==============================================================================
app = FastAPI(title="SOSPEM On-Demand Worker & AI Server")

class ScrapeTriggerPayload(BaseModel):
    job_id: Optional[int] = None
    topic: str
    limit: int = 10
    issue_slug: str = "auto"

@app.get("/")
@app.get("/health")
async def health():
    return {
        "status": "online",
        "busy": SCRAPE_RUNNING.locked(),
        "target_web": BASE_URL,
        "model": MODEL_ID
    }

@app.post("/trigger")
async def trigger_scrape(
    payload: ScrapeTriggerPayload,
    background_tasks: BackgroundTasks,
    x_webhook_secret: str = Header(None)
):
    if x_webhook_secret != WEBHOOK_SECRET:
        raise HTTPException(status_code=401, detail="Invalid webhook secret")

    if SCRAPE_RUNNING.locked():
        return {"status": "busy", "message": "Proses scraping lain sedang berjalan"}

    background_tasks.add_task(
        jalankan_scrape_on_demand,
        payload.job_id,
        payload.topic,
        payload.limit,
        payload.issue_slug
    )
    return {
        "status": "started",
        "job_id": payload.job_id,
        "topic": payload.topic,
        "limit": payload.limit
    }

@app.post("/api/chat")
@app.post("/api/generate")
async def ollama_compatible_chat(req: Request):
    body = await req.json()
    balasan = jawab_chat_sospem(body.get("messages") or [], body.get("prompt") or "")
    return {
        "model": body.get("model", "qwen2.5:1.5b"),
        "created_at": datetime.utcnow().isoformat() + "Z",
        "message": {"role": "assistant", "content": balasan},
        "response": balasan,
        "done": True
    }

if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8001)