# Deployment Guide: Split Hosting Strategy (Hostinger + Windows Server 2025)

You have chosen the **Split Hosting Strategy**, which is often better for performance.
*   **Frontend (Vue.js)**: Runs on Hostinger (Fast, CDNs, always online).
*   **Backend (Laravel + Database)**: Runs on your Windows Server 2025 (Localhost), exposed via Cloudflare Tunnel.

## 1. Requirements

*   **2 Subdomains** on Hostinger:
    1.  `app.yourdomain.com` (Para sa Frontend/Website)
    2.  `api.yourdomain.com` (Para sa Backend/API)
*   **SSL**: Make sure both have SSL (HTTPS). Hostinger usually provides this for the Frontend. Cloudflare Tunnel handles SSL for the Backend automatically.

---

## 2. Server Side (Windows Server 2025)

### Step A: Prepare Backend (XAMPP + Laravel)
1.  Navigate to your backend folder:
    ```powershell
    cd C:\xampp\htdocs\iselco-app\iselco-backend
    ```
3.  **Environment File:**
    Pwede mong kopyahin ang iyong `.env` galing sa development machine, mas madali yun.
    *   *Option A:* Copy-paste mo lang yung laman ng local `.env` mo.
    *   *Option B:* `copy .env.example .env`

    **Important: Baguhin mo ang mga ito para sa Production:**
    ```ini
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://api.yourdomain.com
    FRONTEND_URL=https://app.yourdomain.com
    
    # Reverb (WebSocket) Configuration
    REVERB_HOST=api.yourdomain.com
    REVERB_PORT=443
    REVERB_SCHEME=https
    
    # Database (Local XAMPP)
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=iselco_db
    DB_USERNAME=root
    DB_PASSWORD=
    ```
3.  **Optimize Laravel:**
    ```powershell
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

### Step B: Cloudflare Tunnel (api.yourdomain.com)
You only need to tunnel the **BACKEND**.

1.  **Configure `.cloudflared/config.yml`:**
    ```yaml
    tunnel: <Tunnel-UUID>
    credentials-file: C:\Users\Admin\.cloudflared\<Tunnel-UUID>.json

    ingress:
      # Route Reverb WebSockets (Uses the same API domain)
      # Note: Reverb usually listens on /app path for connections
      - hostname: api.yourdomain.com
        path: /app*
        service: http://localhost:8080

      # Route API Requests to Apache
      - hostname: api.yourdomain.com
        service: http://localhost:80

      - service: http_status:404
    ```
2.  **DNS Update:**
    Run this to point `api.yourdomain.com` to your tunnel:
    ```powershell
    cloudflared tunnel route dns iselco-server api.yourdomain.com
    ```
3.  **Restart Tunnel:**
    ```powershell
    cloudflared service restart
    ```
    *Make sure the tunnel service is running (Step F from previous guide).*

---

### Step C: Running Background Processes (Automated via NSSM)
Para sa Windows Server, **NSSM** (Non-Sucking Service Manager) ang recommended standard kaysa sa PM2 (na pang Node.js) o simpleng Task Scheduler. Mas stable ang NSSM dahil ginagawa nitong native Windows Service ang PHP command, kaya automatic itong mag-start kapag nag-boot ang server at mag-restart kapag nag-crash.

1.  **Download NSSM:**
    *   Download mo galing sa [nssm.cc](https://nssm.cc/download).
    *   Extract `nssm.exe` (piliin ang win64 folder) papunta sa `C:\xampp\nssm.exe` (o kahit saan na accessible sa PATH).

2.  **Open PowerShell as Administrator.**

3.  **Setup Reverb Service:**
    *Palitan ang `C:\Your\Path\To` ng actual path kung saan mo nilagay ang extracted app mo.*
    ```powershell
    C:\xampp\nssm.exe install IselcoReverb "C:\xampp\php\php.exe"
    C:\xampp\nssm.exe set IselcoReverb AppParameters "artisan reverb:start --host=0.0.0.0 --port=8080"
    C:\xampp\nssm.exe set IselcoReverb AppDirectory "C:\Your\Actual\Project\Path\iselco-backend"
    C:\xampp\nssm.exe set IselcoReverb AppStdout "C:\Your\Actual\Project\Path\iselco-backend\storage\logs\reverb.log"
    C:\xampp\nssm.exe set IselcoReverb AppStderr "C:\Your\Actual\Project\Path\iselco-backend\storage\logs\reverb-error.log"
    C:\xampp\nssm.exe start IselcoReverb
    ```

4.  **Setup Queue Service:**
    ```powershell
    C:\xampp\nssm.exe install IselcoQueue "C:\xampp\php\php.exe"
    C:\xampp\nssm.exe set IselcoQueue AppParameters "artisan queue:work --tries=3 --timeout=90"
    C:\xampp\nssm.exe set IselcoQueue AppDirectory "C:\Your\Actual\Project\Path\iselco-backend"
    C:\xampp\nssm.exe set IselcoQueue AppStdout "C:\Your\Actual\Project\Path\iselco-backend\storage\logs\queue.log"
    C:\xampp\nssm.exe set IselcoQueue AppStderr "C:\Your\Actual\Project\Path\iselco-backend\storage\logs\queue-error.log"
    C:\xampp\nssm.exe start IselcoQueue
    ```

5.  **Configure Cloudflare Tunnel as a Service:**
    ```powershell
    cloudflared service install
    ```

### Step D: Apache Config (Using Virtual Hosts)
Para makapag-run ka ng **maraming apps** sa iisang server sa future, `Virtual Hosts` ang gagamitin natin. Wag mong baguhin ang main `DocumentRoot`.

1.  Open **XAMPP Control Panel** -> Apache -> Config -> `httpd-vhosts.conf` (Usually nasa `apache/conf/extra/`).
2.  Idagdag ito sa dulo ng file (Siguraduhing walang `#` sa unahan ng `NameVirtualHost`):

    ```apache
    # Ito ang para sa ISELCO Backend (Listening on Port 80)
    <VirtualHost *:80>
        ServerAdmin admin@iselcouno.com
        DocumentRoot "D:/WEB_APPS/ISELCO1STAR-V2/iselco-backend/public"
        ServerName apistar.iselcouno.com
        
        <Directory "D:/WEB_APPS/ISELCO1STAR-V2/iselco-backend/public">
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
        
        ErrorLog "logs/iselco-error.log"
        CustomLog "logs/iselco-access.log" common
    </VirtualHost>
    ```

3.  **Enable Virtual Hosts:**
    Open `httpd.conf` again. Hanapin ang line na:
    `#Include conf/extra/httpd-vhosts.conf`
    **Tanggalin ang `#`** sa unahan para mag-activate.

4.  **Restart Apache.**

---

## 5. Frequently Asked Questions (DNS & Subdomains)

### Q: Buburahin ko ba ang `apistar` subdomain sa Hostinger?
**OO.** Dahil ang Cloudflare Tunnel ang hahawak nito.
*   **Star (Frontend):** KEEP sa Hostinger. Ito ang static files.
*   **Apistar (Backend):** DELETE sa Hostinger DNS.

### Q: Paano magagawa yung `apistar.iselcouno.com`?
Kapag ni-run mo ang command na ito sa Tunnel setup:
`cloudflared tunnel route dns iselco-server apistar.iselcouno.com`
...si Cloudflare na mismo ang gagawa ng DNS entry (CNAME) sa account mo. Matic 'yan.

### Q: Pwede bang `php artisan serve` na lang? (Ayoko ng XAMPP config)
**Pwede, pero hindi recommended.**
Ang `php artisan serve` ay pang-development lang. Mabagal ito at pwedeng mamatay.
Pero kung gusto mo talaga, pwede mong i-point ang Tunnel Config mo sa `http://localhost:8000` at mag-run ka ng serve manually (o via NSSM).
*   **Recommendation:** Sanayin mo ang Apache Virtual Hosts. Mas pro at stable yun.

### Step A: Build Frontend
1.  Go to your local `iselco-frontend` folder.
2.  Update `.env.production` (Create if not exists):
    ```ini
    # Point to the Cloudflare Tunnel URL
    VITE_API_URL=https://api.yourdomain.com/api
    
    # Point Reverb to the same Tunnel URL
    VITE_REVERB_APP_KEY=your_app_key
    VITE_REVERB_HOST=api.yourdomain.com
    VITE_REVERB_PORT=443
    VITE_REVERB_SCHEME=https
    ```
3.  **Build the Project:**
    ```powershell
    npm run build
    ```
    This will create a `dist` folder.

### Step B: Upload to Hostinger
1.  Go to Hostinger Dashboard -> **Subdomains** -> Create `app.yourdomain.com`.
2.  Go to **File Manager** -> Access files for `app.yourdomain.com`.
3.  **Upload:**
    *   Upload all files **INSIDE** the `dist` folder to the `public_html` of your subdomain.
    *   *Do not upload the `dist` folder itself, just the contents.*
4.  **SPA Routing (Important):**
    Since this is a Vue SPA, you need a `.htaccess` file in Hostinger `public_html` so that refreshing pages works.
    *   Create a file named `.htaccess` in `public_html`:
    ```apache
    <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /
      RewriteRule ^index\.html$ - [L]
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule . /index.html [L]
    </IfModule>
    ```

---

## 4. CORS (Cross-Origin Setup)
Since your API is on `api.` and Frontend is on `app.`, you need to allow this cross-communication.

1.  Open `iselco-backend/config/cors.php` on your Server.
2.  Make sure it allows your frontend:
    ```php
    'allowed_origins' => ['https://app.yourdomain.com', 'http://localhost:5173'],
    // OR just keep it permissive if you want:
    // 'allowed_origins' => ['*'],
    ```
3.  Clear cache if you changed this: `php artisan config:clear`.

---

## Summary of Traffic Flow
1.  **User** visits `https://app.yourdomain.com` (Hostinger).
2.  **Vue App** loads and makes Login request to `https://api.yourdomain.com/api/login`.
3.  **Request** goes to Cloudflare -> Tunnel -> Your Windows Server (Apache).

---

## 5. Frequently Asked Questions (Configuration & Tunnel)

### Q: Bakit kailangan ilagay ang public URL (`https://api.yourdomain.com`) sa `.env` kahit nasa localhost ang backend?
**Sagot:**
Kahit nasa localhost ang backend mo, ang **nag-a-access** nito ay ang Frontend na nasa internet (Hostinger) at ang mga Users.
1.  **Link Generation:** Kapag gumawa ang Laravel ng links (halimbawa: email verification, image paths), ginagamit nito ang `APP_URL`. Kung `localhost` ang nakalagay, `http://localhost/...` ang ibibigay niyang link sa user, na **hindi gagana** sa computer nila.
2.  **CORS & Security:** Tintignan ng browser kung match ang request source. Kung hindi alam ng backend na siya ay `api.yourdomain.com`, baka i-block niya ang requests.

### Q: Paano ba gumagana ang Tunnel Process?
Tama ang intindi mo. Ganito ang flow:
1.  **Localhost Backend**: Umaandar lang sa computer mo (XAMPP).
2.  **Cloudflare Tunnel**: Isang maliit na program (`cloudflared`) sa computer mo na gumagawa ng "secret bridge" papunta sa Cloudflare.
3.  **The Connection**:
    *   User -> Internet -> `api.yourdomain.com` (Cloudflare) -> **Tunnel** -> Computer mo -> Localhost Backend.
4.  Kaya **hindi** mo kailangan mag-open ng ports sa router o firewall. Si Tunnel ang bahala.

### Q: Kailangan ko pa ba i-upload ang backend files?
**Hindi.** Ang backend files ay mananatili sa Windows Server mo. Ang i-upload mo lang sa Hostinger ay ang `dist` folder ng Frontend (Vue).

