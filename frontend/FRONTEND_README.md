# Frontend - Gerador de Catálogo

## 🎨 Interface do Usuário

O frontend agora oferece duas opções de geração de catálogo:

### 1. **Modo Automático (Recomendado)** ⚡
- Processa automaticamente em lotes se necessário
- Ideal para qualquer tamanho de catálogo
- Download imediato quando pronto
- Mais simples e rápido

### 2. **Modo Assíncrono** 🔄
- Processamento em background com jobs
- Mostra progresso em tempo real por lote
- Ideal para catálogos muito grandes (1000+ produtos)
- Permite navegar enquanto processa

## 🚀 Como Usar

### Desenvolvimento
```bash
cd frontend
npm install
npm run dev
```

O frontend estará disponível em: `http://localhost:5173`

### Produção
```bash
npm run build
```

## 🎯 Funcionalidades

### Filtros
- ✅ **Funko** - Filtrar produtos da marca Funko
- ✅ **Blokees** - Filtrar produtos da marca Blokees
- ✅ **Mostrar Estoque** - Incluir informações de estoque no PDF

### Modos de Geração

#### Automático
1. Selecione os filtros desejados
2. Marque "Automático (Recomendado)"
3. Clique em "Gerar Catálogo"
4. Aguarde o processamento
5. PDF será baixado automaticamente

**Como funciona:**
- Até 100 produtos → Gera imediatamente
- Mais de 100 produtos → Divide em lotes de 50 e junta os PDFs

#### Assíncrono
1. Selecione os filtros desejados
2. Marque "Assíncrono (Catálogos grandes)"
3. Clique em "Gerar Catálogo"
4. Acompanhe o progresso por lote
5. PDF será baixado quando todos os lotes terminarem

**Como funciona:**
- Envia jobs para o Laravel Queue
- Cada lote processa 50 produtos
- Mostra progresso em tempo real
- Junta todos os PDFs no final

## 📊 Indicadores Visuais

- **Barra de Progresso** - Mostra percentual de conclusão
- **Informação de Lotes** - "Processando lote X de Y"
- **Mensagens de Status** - Feedback em tempo real
- **Mensagens de Sucesso** - Confirmação de download
- **Mensagens de Erro** - Avisos em caso de falha

## 🔧 Configuração

### API Endpoint
O frontend se conecta ao backend em: `http://localhost:8000`

Para alterar, modifique as URLs em `App.vue`:
```javascript
fetch('http://SEU_SERVIDOR:8000/api/catalog/generate', ...)
```

### Variáveis de Ambiente (opcional)
Crie um arquivo `.env.local`:
```
VITE_API_URL=http://localhost:8000
```

E use no código:
```javascript
const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
```

## 🎨 Personalização

### Cores
As cores principais estão definidas no CSS:
- **Primária**: `#04abeb` (azul)
- **Secundária**: `#fbd304` (amarelo)
- **Sucesso**: `#84bc74` (verde)
- **Erro**: `#ff6b6b` (vermelho)

### Layout
O layout é responsivo e se adapta a diferentes tamanhos de tela.

## 🐛 Troubleshooting

### "Erro ao gerar catálogo"
- Verifique se o backend está rodando
- Confirme que a URL da API está correta
- Veja o console do navegador para mais detalhes

### "Erro ao verificar progresso"
- Verifique se o queue worker está rodando: `php artisan queue:work`
- Confirme que as tabelas de cache/jobs existem no banco

### PDF não baixa
- Verifique bloqueadores de pop-up no navegador
- Confirme que o backend retornou o arquivo corretamente
- Veja a aba Network no DevTools

## 📱 Responsividade

O sistema é totalmente responsivo e funciona em:
- 💻 Desktop
- 📱 Tablets
- 📱 Smartphones

## ⚡ Performance

### Otimizações
- Polling inteligente de progresso (a cada 2 segundos)
- Cleanup de intervalos quando componente desmonta
- Mensagens de erro/sucesso com auto-dismiss
- Animações suaves com CSS

### Boas Práticas
- Desabilita botão durante processamento
- Mostra feedback visual constante
- Limpa estado após conclusão
- Trata erros graciosamente

## 🔗 Integração com Backend

### Endpoints Utilizados
```
POST   /api/catalog/generate          → Geração automática
POST   /api/catalog/generate-async    → Iniciar assíncrona
GET    /api/catalog/progress/{id}     → Verificar progresso
GET    /api/catalog/download/{id}     → Baixar PDF
```

### Fluxo de Dados

**Modo Automático:**
```
Frontend → POST /generate → Backend processa → PDF retorna
```

**Modo Assíncrono:**
```
Frontend → POST /generate-async → Session ID
Frontend → GET /progress/{id} (polling) → Status/Progresso
Backend → Processa lotes em background
Frontend → GET /download/{id} → PDF
```

## 📚 Tecnologias

- **Vue.js 3** - Framework JavaScript
- **Fetch API** - Requisições HTTP
- **CSS3** - Estilização e animações
- **Vite** - Build tool

## 🎯 Próximos Passos

Possíveis melhorias futuras:
- [ ] Modo escuro
- [ ] Salvar preferências de filtros
- [ ] Histórico de catálogos gerados
- [ ] Preview do catálogo antes de baixar
- [ ] Notificações push quando pronto
- [ ] WebSocket para progresso em tempo real
