# 17 - Cost Projection & Scaling - V9 Shared Host - 600 to 5000 Students

## Current Cost for 600 Students V9 Fixed (Shop + 40GB Extra = 50GB)

From deployment guide and hosting requirements:

- **Cloud Host Shop 10GB SSD / 5 vCPU / 7GB RAM / Unlimited Bandwidth**: 588k-716,550 Toman/month (base)
- **Extra Block Storage 40GB add-on via Pars Pack ticket**: ~300k-500k Toman/month (ask support)
- **Total 50GB for truly evergreen permanent**: ~1.2-1.5M Toman/month
- **Domain .ir**: ~50k Toman/year = ~4k/month
- **Pushe**: Free up to 10k devices (600 students <10k, free)
- **Kavenegar SMS optional**: 100-200 Toman per SMS, 1000 SMS/month critical alerts = 100k-200k/month
- **Arvan S3 offsite backup 100GB cold**: ~200k/month (optional but recommended for H6)
- **Total MVP 600 students**: ~1.5-2.2M Toman/month (Shop 50GB + domain + Pushe free + S3 backup)

For comparison:
- Old VPS plan 4c/16GB/500GB + backup VPS 2M = 8-13M/month, save 90% with shared host.

## Cost Breakdown: Where Money Goes

- 70% Hosting (Cloud Host + extra disk)
- 10% Domain + SSL (Let's Encrypt free, .ir 50k/year)
- 10% SMS optional (Kavenegar)
- 10% Offsite backup (Arvan S3)

## Scaling Projections

### 600 Students (Current - Start)

- **Storage Calculation Realistic per Semester:**
  - Student uploads: 600 students * 1 file avg * 5MB = 3GB / semester
  - Professor uploads: 40 courses * 2 specs * 5 files * 5MB = 2GB / semester
  - Ticket images: 600 students * 2 tickets avg * 1 image * 2MB = 2.4GB / semester
  - Assignment attachments: 600*3 assignments*2MB = 3.6GB / semester
  - Total new per semester: ~11GB per semester
  - Plus versions double: ~15GB per semester for first year
  - 4 years evergreen: 8 semesters * 15GB = 120GB total needed for truly evergreen permanent
  - **Conclusion:** 50GB enough for 2-3 years (6 semesters *15GB=90GB actually 50GB enough for ~3 semesters). For 4 years need 120GB, need to either:
    - Upgrade to 100GB custom disk add-on (~2.5M/month) OR
    - Implement cold archiving: Hot 50GB for current + 1 past semester, older auto-move to Arvan S3 cold via rclone (cost 200k/month for 100GB cold)

- **Bandwidth Calculation:**
  - 600 students * 10 resources *5MB = 30GB/day during exam week (downloading)
  - 30GB/day *30 = 900GB/month
  - Fair usage on Cloud Host unlimited but typical fair usage 2TB/month, 900GB okay
  - If each student downloads 50 resources 50MB = 600*50*50MB=1.5TB/day = 45TB/month -> suspend, need rate limiting 20 downloads per student per day via download_daily_counts table (FIX H5)
  - Implemented: Max 20 downloads per student per day, 600*20*5MB=60GB/day = 1.8TB/month still under 2TB fair usage

- **Concurrency:**
  - Polling: 600 users * 1 req/30s (fixed) = 20 req/s average, with 5s file cache hit 80% -> ~4 req/s DB queries, okay for 5 vCPU
  - Enrollment peak: 200 concurrent finalizing at same second, each request 50-100ms DB, needs 20 concurrent MySQL connections average, MySQL max_connections 100-200 on shared host, okay but borderline, need file cache for polling endpoint + increase polling interval to 30s (already fixed C5)

### 1200 Students (Double - Next Year)

- Storage: ~22GB per semester new, 44GB per year, 4 years = 176GB needed, need 100GB hot + 100GB cold S3
- Cost: Cloud Host Shop 10GB base + 90GB extra block storage = ~100GB total ~2.5M/month + Arvan S3 100GB cold 200k = 2.7M/month
- Bandwidth: 1200*10*5MB=60GB/day=1.8TB/month, still under 2TB fair usage if rate limited 20/day, but need to monitor
- Concurrency: Polling 1200 users *1 req/30s =40 req/s average, file cache 5s hit 80% -> 8 req/s DB, okay, but enrollment peak 400 concurrent -> MySQL max_connections 100-200 will be exceeded -> need to upgrade to dedicated server or implement queue for enrollment finalization (stagger via lottery, e.g., final year first, then GPA_A, then normal)

### 2000 Students

- Must move from shared host to VPS or dedicated server - shared host 5 vCPU 7GB RAM will not handle 2000 polling + enrollment peak 600 concurrent, will 503 and be suspended for CPU abuse.
- Recommended: Migrate to Iranserver NGP-large80 4c/16GB/80GB 4.1M/month + extra block storage 500GB ~2M = 6M total + Arvan S3 backup
- Cost: ~6-8M/month for VPS, still cheaper than 8-13M old estimate for 2000 but more than shared host 50GB 1.5M

### 5000 Students (University-Wide)

- Must move to dedicated server or cluster: Bare-metal Iran with dual E5-2670 16 cores 32GB RAM 250GB SSD + 800GB inbound free 5.15M/month (from Pars Pack dedicated) + second server for DB + MinIO S3 self-hosted 2TB HDD
- Cost: ~10-15M/month for 2 servers + CDN Arvan + S3 backup
- Architecture: Split into microservices? No, Laravel monolith still okay for 5000 with caching, but need Redis (not file cache) for rate limiting and queue, need to move back to VPS with Redis.

## Migration Path from Shared Host to VPS (When Scaling Beyond 600)

1. Buy Iranserver NGP-large80 4c/16GB/80GB 4.1M/month (or Pars Pack dedicated irBMServer-ECONOMY 2x E5-2670 16 cores 32GB RAM 250GB SSD 5.15M/month)
2. Install Docker + Docker Compose with FastAPI? Actually keep Laravel but add Redis
3. Dump MySQL from shared host cPanel phpMyAdmin export, import to new VPS MySQL
4. Copy /uploads folder via rsync or rclone from shared host to new VPS /data/minio or /var/www/uploads
5. Change DNS A record from old Cloud Host IP to new VPS IP
6. Test polling + enrollment peak with k6 500 concurrent
7. Keep old Cloud Host as backup for 1 week

## Cost Saving Tips for 600 Students MVP

- Use Shop 10GB + 40GB extra = 50GB total, not 100GB, enough for 2-3 semesters
- Use Pushe free tier up to 10k devices (600 <10k free)
- Use Let's Encrypt free SSL, not paid
- Use Cache API client-side to avoid re-downloading same PDF, saves bandwidth
- Implement download daily limit 20 per student per day to stay under fair usage 2TB
- Use university lab PC for offsite backup rclone to avoid paying Arvan S3, just external HDD weekly manual backup

## Final Recommendation for 600 Students Start

- Buy Cloud Host Shop 10GB base + 40GB extra block storage = 50GB total ~1.2-1.5M/month
- Domain .ir 50k/year
- Pushe free
- No Kavenegar SMS for MVP to save 200k (optional later)
- No Arvan S3 offsite backup for MVP, use manual external HDD weekly backup to save 200k (but risk)
- **Total MVP: ~1.2-1.5M/month for truly evergreen permanent 50GB, handles 600 students, 200 concurrent enrollment peak, 40 req/s polling with 5s cache**

If even 1.2M too much, start with Startup 5GB/3vCPU/4GB RAM 341k + 20GB extra = 25GB total ~700k-800k/month, enough for 1 year, then upgrade.

END COST PROJECTION
