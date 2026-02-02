# Catalogeek - Gerador de Catálogo de Produtos

Sistema de geração de catálogos de produtos em PDF com filtros personalizados.

## Tecnologias

- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: Vue 3
- **PDF**: DomPDF
- **Docker**: Para containerização (opcional)

## Requisitos

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL (banco de dados existente: nerdrop)

## Instalação

### Desenvolvimento (Sem Docker)

1. Instale as dependências do backend:
```bash
cd backend
composer install
```

2. Configure o arquivo .env (já configurado com as credenciais fornecidas)

3. Instale as dependências do frontend:
```bash
cd ../frontend
npm install
```

4. Inicie o projeto:
```bash
cd ..
./start.sh
```

Ou manualmente:

Terminal 1 (Backend):
```bash
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

Terminal 2 (Frontend):
```bash
cd frontend
npm run dev
```

### Com Docker

```bash
docker-compose up -d
```

## Acessos

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api
- **Com Docker**: http://localhost:8400

## Funcionalidades

### Filtros Disponíveis

- **Funko**: Filtra produtos da marca Funko
- **Blokees**: Filtra produtos da marca Blokee
- **Mostrar Estoque**: Inclui ou exclui produtos sem estoque

### Geração de PDF

1. Selecione os filtros desejados
2. Clique em "Gerar Catálogo"
3. Acompanhe o progresso
4. O PDF será baixado automaticamente

## Estrutura do Banco de Dados

Tabela: `products`
- Campos principais: id, name, price, sku, stock, marca
- Relação com `product_images` para imagens dos produtos

## Paleta de Cores

A interface utiliza as cores da empresa:
- Amarelo: #fbd304
- Azul: #04abeb, #44b6e6
- Verde: #84bc74
- Cinza: #646463

## API Endpoints

### POST /api/catalog/generate
Gera o catálogo em PDF

**Body:**
```json
{
  "funko": true/false,
  "blokees": true/false,
  "showStock": true/false
}
```

**Response:** PDF file download

## Desenvolvimento

### Backend (Laravel)
- Model: `app/Models/Product.php`
- Controller: `app/Http/Controllers/CatalogController.php`
- View PDF: `resources/views/catalog.blade.php`
- Routes: `routes/api.php`

### Frontend (Vue)
- Componente principal: `src/App.vue`
- Sem router, pinia ou outras libs desnecessárias

## Notas

- O sistema conecta ao banco MySQL existente em 192.168.15.32:8888
- PDF gerado em formato A4 com layout em grid (3 colunas)
- Barra de progresso simulada para melhor UX
- CORS habilitado para comunicação frontend-backend
