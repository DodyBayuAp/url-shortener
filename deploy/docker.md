# 🐳 Docker Deployment Guide

This guide details how to deploy the PHP URL Shortener using Docker and Docker Compose.

## 📋 Prerequisites

- [Docker Engine](https://docs.docker.com/engine/install/)
- [Docker Compose](https://docs.docker.com/compose/install/)

## 🚀 Quick Start (Docker Compose)

The easiest way to run the application is using Docker Compose.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/DodyBayuAp/url-shortener
   cd url-shortener
   ```

2. **Start the service:**
   ```bash
   docker-compose up -d
   ```

3. **Access the application:**
   Open [http://localhost:8080](http://localhost:8080) in your browser.

4. **Stop the service:**
   ```bash
   docker-compose down
   ```

---

## 🛠 Manual Build & Run

If you prefer to run `docker` commands manually:

### 1. Build the Image

```bash
docker build -t url-shortener .
```

### 2. Run the Container

**Linux / macOS:**
```bash
docker run -d -p 8080:80 --name url-shortener \
  -v $(pwd)/data:/var/www/html/data \
  url-shortener
```

**Windows (PowerShell):**
```powershell
docker run -d -p 8080:80 --name url-shortener -v ${PWD}/data:/var/www/html/data url-shortener
```

**Windows (Command Prompt / cmd):**
```cmd
docker run -d -p 8080:80 --name url-shortener -v %cd%/data:/var/www/html/data url-shortener
```

---

## ⚙️ Configuration

### Environment Variables

You can configure the application using environment variables in `docker-compose.yml`:

| Variable | Description | Default |
|----------|-------------|---------|
| `PHP_TIMEZONE` | Set the PHP timezone | `UTC` |

### Database

By default, the application uses **SQLite** which stores data in the `data/` folder. This is persisted via the Docker volume mapping.

To use **MySQL** or **PostgreSQL**, uncomment the respective sections in `docker-compose.yml`.

---

## ❓ Troubleshooting

### Error: "The container name is already in use"

**Problem:** You try to run the container but get a conflict error correctly.
```
Error response from daemon: Conflict. The container name "/url-shortener" is already in use...
```

**Solution:** Remove the old container first:
```bash
docker rm -f url-shortener
```
Then try running it again.

### Error: PHP Code Displayed Instead of App

**Problem:** You see raw PHP code when accessing the site.
**Solution:** Ensure you are using the latest `Dockerfile` which includes the correct Apache configuration. Rebuild the image:
```bash
docker build -t url-shortener .
```

### Permission Issues (Linux)

If you cannot write to the database:
```bash
chown -R 33:33 data/
```
(User ID 33 is the default `www-data` user in the container)
