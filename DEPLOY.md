# 🚀 Deploy do Catalogeek

## Estrutura de Produção

O sistema usa **Supervisor** para manter os processos rodando:
- ✅ Nginx (servidor web)
- ✅ PHP-FPM (processador PHP)
- ✅ Laravel Queue Worker (2 processos para processar catálogos)

Se o worker cair, o Supervisor reinicia automaticamente.

---

## 📦 Deploy no Servidor

### 1. Preparar o Servidor

```bash
# Instalar Docker e Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo apt-get install docker-compose-plugin
```

### 2. Clonar o Projeto

```bash
git clone <seu-repositorio> catalogeek
cd catalogeek
```

### 3. Configurar Variáveis de Ambiente

```bash
cp backend/.env.example backend/.env
nano backend/.env
```

Configure as variáveis no `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://seu-dominio.com

DB_HOST=164.92.67.204
DB_PORT=3307
DB_DATABASE=nerdrop
DB_USERNAME=root
DB_PASSWORD=DropNOlemq10w

QUEUE_CONNECTION=database
CACHE_DRIVER=database

# Gerar nova chave
APP_KEY=base64:...
```

### 4. Gerar APP_KEY

```bash
docker run --rm -v $(pwd)/backend:/app -w /app php:8.3-cli php artisan key:generate
```

### 5. Build e Iniciar

```bash
# Build da imagem
docker compose -f docker-compose.prod.yml build

# Subir o container
docker compose -f docker-compose.prod.yml up -d
```

### 6. Verificar Logs

```bash
# Logs gerais
docker compose -f docker-compose.prod.yml logs -f

# Logs do worker (fila)
docker exec catalogeek_prod tail -f /var/www/backend/storage/logs/worker.log

# Status do Supervisor
docker exec catalogeek_prod supervisorctl status
```

---

## 🔄 Atualizar o Sistema

```bash
# 1. Fazer pull das alterações
git pull

# 2. Rebuild e restart
docker compose -f docker-compose.prod.yml up -d --build

# 3. Limpar cache dentro do container
docker exec catalogeek_prod php artisan cache:clear
docker exec catalogeek_prod php artisan config:cache
docker exec catalogeek_prod php artisan route:cache
```

---

## 🛠️ Comandos Úteis

### Gerenciar Filas (Worker)

```bash
# Ver status dos processos
docker exec catalogeek_prod supervisorctl status

# Reiniciar worker
docker exec catalogeek_prod supervisorctl restart laravel-worker:*

# Parar worker
docker exec catalogeek_prod supervisorctl stop laravel-worker:*

# Iniciar worker
docker exec catalogeek_prod supervisorctl start laravel-worker:*
```

### Limpar Catálogos Temporários

```bash
# Manualmente
docker exec catalogeek_prod php artisan catalogs:clean-temp --hours=24

# Adicionar ao crontab (dentro do container)
docker exec catalogeek_prod crontab -e
# Adicionar: 0 3 * * * cd /var/www/backend && php artisan catalogs:clean-temp --hours=48
```

### Baixar Imagens dos Produtos

```bash
docker exec catalogeek_prod php artisan products:download-images
```

### Acessar o Container

```bash
docker exec -it catalogeek_prod bash
```

---

## 📊 Monitoramento

### Verificar se o Worker Está Rodando

```bash
docker exec catalogeek_prod supervisorctl status laravel-worker:*
```

Saída esperada:
```
laravel-worker:laravel-worker_00   RUNNING   pid 123, uptime 1:23:45
laravel-worker:laravel-worker_01   RUNNING   pid 124, uptime 1:23:45
```

### Ver Filas Pendentes

```bash
docker exec catalogeek_prod php artisan queue:monitor database
```

### Ver Jobs Falhados

```bash
docker exec catalogeek_prod php artisan queue:failed
```

---

## ⚠️ Troubleshooting

### Worker Não Processa Jobs

```bash
# 1. Verificar se está rodando
docker exec catalogeek_prod supervisorctl status

# 2. Ver logs
docker exec catalogeek_prod tail -f /var/www/backend/storage/logs/worker.log

# 3. Reiniciar manualmente
docker exec catalogeek_prod supervisorctl restart laravel-worker:*
```

### PDFs Não São Gerados

```bash
# Verificar permissões
docker exec catalogeek_prod ls -la /var/www/backend/storage/app/private/temp_catalogs/

# Ajustar se necessário
docker exec catalogeek_prod chown -R www-data:www-data /var/www/backend/storage
```

### Espaço em Disco

```bash
# Ver uso
docker exec catalogeek_prod df -h

# Limpar catálogos antigos
docker exec catalogeek_prod php artisan catalogs:clean-temp --hours=12
```

---

## 🔐 Nginx Reverse Proxy (Opcional)

Se quiser usar um domínio com HTTPS:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;

    location / {
        proxy_pass http://localhost:8400;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeout maior para PDFs grandes
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }
}
```

Depois adicione SSL com Certbot:
```bash
sudo certbot --nginx -d seu-dominio.com
```

---

## 📝 Notas Importantes

1. **Supervisor vs Horizon**: Estamos usando Supervisor com `queue:work` ao invés do Horizon porque:
   - Mais simples e leve
   - Não precisa de Redis
   - Supervisor já reinicia automaticamente se o processo cair

2. **2 Workers**: O sistema roda 2 processos do queue worker para processar batches em paralelo

3. **Timeout**: O worker tem timeout de 1 hora (`--max-time=3600`) para processar catálogos grandes

4. **Logs**: Tudo é logado em `/var/www/backend/storage/logs/`

5. **Storage**: O volume `./backend/storage` é persistido fora do container para não perder os PDFs

---

## 🎯 Checklist Pós-Deploy

- [ ] Container está rodando: `docker ps`
- [ ] Worker está ativo: `docker exec catalogeek_prod supervisorctl status`
- [ ] API responde: `curl http://localhost:8400/api/catalog/generate-async -d ''`
- [ ] Frontend carrega: Acessar `http://localhost:8400`
- [ ] Testar geração de catálogo
- [ ] Verificar logs: `docker logs catalogeek_prod`
