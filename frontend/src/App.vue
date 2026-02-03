<template>
  <div id="app">
    <div class="container">
      <header>
        <img src="/logo.png" alt="Logo" class="logo">
        <h1>Sistema de Geração de Catálogo para Vendedores</h1>
      </header>

      <main>
        <div class="filters-card">
          <h2>Selecione os Filtros</h2>
          
          <div class="filter-group">
            <div class="filter-row">
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.funko">
                <span>Funko</span>
              </label>
              
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.blokees">
                <span>Blokees</span>
              </label>
            </div>
            
            <div class="filter-row stock-row">
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.showStock">
                <span>Mostrar Estoque no Catálogo</span>
              </label>
            </div>
          </div>

          <button 
            @click="generateCatalog" 
            :disabled="isGenerating"
            class="btn-generate"
          >
            <span v-if="!isGenerating">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
              </svg>
              Gerar Catálogo
            </span>
            <span v-else>
              <div class="spinner"></div>
              Gerando...
            </span>
          </button>
        </div>

        <div v-if="isGenerating" class="progress-card">
          <div class="progress-header">
            <h3>{{ statusMessage }}</h3>
            <span class="progress-percentage">{{ progress }}%</span>
          </div>
          
          <div class="progress-bar-container">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: progress + '%' }">
                <div class="progress-shine"></div>
              </div>
            </div>
          </div>
          
          <p v-if="batchInfo" class="batch-info">
            <span class="batch-current">{{ batchInfo.completed }}</span> de 
            <span class="batch-total">{{ batchInfo.total }}</span> lotes processados
          </p>
        </div>

        <div v-if="success" class="success-message">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          {{ success }}
        </div>

        <div v-if="error" class="error-message">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          {{ error }}
        </div>
      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      filters: {
        funko: false,
        blokees: false,
        showStock: false
      },
      isGenerating: false,
      progress: 0,
      error: null,
      success: null,
      statusMessage: 'Gerando catálogo...',
      batchInfo: null,
      sessionId: null,
      progressInterval: null
    }
  },
  methods: {
    async generateCatalog() {
      this.isGenerating = true;
      this.progress = 0;
      this.error = null;
      this.success = null;
      this.batchInfo = null;

      await this.generateAsync();
    },

    async generateAsync() {
      this.statusMessage = 'Iniciando processamento em background...';
      
      try {
        // Iniciar geração assíncrona
        const response = await fetch('/api/catalog/generate-async', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.filters)
        });

        if (!response.ok) {
          throw new Error('Erro ao iniciar geração assíncrona');
        }

        const data = await response.json();
        
        // Verificar se há erro na resposta
        if (data.error) {
          throw new Error(data.error);
        }
        
        this.sessionId = data.session_id;
        this.batchInfo = {
          completed: 0,
          total: data.total_batches
        };

        this.statusMessage = `Processando ${data.total_products} produtos em ${data.total_batches} lotes...`;
        
        // Verificar progresso periodicamente
        this.progressInterval = setInterval(() => {
          this.checkProgress();
        }, 2000);

      } catch (err) {
        this.error = err.message || 'Erro ao iniciar geração. Por favor, tente novamente.';
        this.isGenerating = false;
        this.progress = 0;
        
        setTimeout(() => {
          this.error = null;
        }, 5000);
      }
    },

    async checkProgress() {
      if (!this.sessionId) return;

      try {
        const response = await fetch(`/api/catalog/progress/${this.sessionId}`);
        
        if (!response.ok) {
          throw new Error('Erro ao verificar progresso');
        }

        const data = await response.json();
        this.progress = data.percentage;
        this.batchInfo = {
          completed: data.completed,
          total: data.total
        };

        if (data.status === 'completed') {
          clearInterval(this.progressInterval);
          this.statusMessage = 'Juntando PDFs e preparando download...';
          this.progress = 100;
          
          // Aguardar um pouco antes de fazer download
          setTimeout(() => {
            this.downloadCatalog();
          }, 1000);
        }

      } catch (err) {
        clearInterval(this.progressInterval);
        this.error = 'Erro ao verificar progresso. Por favor, tente novamente.';
        this.isGenerating = false;
        
        setTimeout(() => {
          this.error = null;
        }, 5000);
      }
    },

    async downloadCatalog() {
      if (!this.sessionId) return;

      try {
        const response = await fetch(`/api/catalog/download/${this.sessionId}`);
        
        if (!response.ok) {
          throw new Error('Catálogo ainda não está pronto. Por favor, aguarde.');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'catalogo-produtos.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        this.success = 'Catálogo gerado e baixado com sucesso!';

        setTimeout(() => {
          this.isGenerating = false;
          this.progress = 0;
          this.success = null;
          this.sessionId = null;
          this.batchInfo = null;
        }, 3000);

      } catch (err) {
        this.error = err.message || 'Erro ao baixar catálogo.';
        this.isGenerating = false;
        
        setTimeout(() => {
          this.error = null;
        }, 5000);
      }
    }
  },

  beforeUnmount() {
    if (this.progressInterval) {
      clearInterval(this.progressInterval);
    }
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f5f7fa;
  min-height: 100vh;
}

#app {
  min-height: 100vh;
  padding: 20px;
}

.container {
  max-width: 900px;
  margin: 0 auto;
}

header {
  text-align: center;
  margin-bottom: 40px;
  padding: 40px 20px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.logo {
  max-width: 180px;
  height: auto;
  margin-bottom: 20px;
}

header h1 {
  color: #2d3748;
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.5px;
}

main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.filters-card {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.filters-card h2 {
  color: #2d3748;
  margin-bottom: 24px;
  font-size: 1.25rem;
  font-weight: 600;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 28px;
}

.filter-row {
  display: flex;
  gap: 24px;
  align-items: center;
}

.stock-row {
  margin-top: 8px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 1rem;
  color: #4a5568;
  transition: all 0.2s;
  user-select: none;
}

.checkbox-label:hover {
  color: #2d3748;
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  margin-right: 10px;
  cursor: pointer;
  accent-color: #4299e1;
}

.btn-generate {
  width: 100%;
  padding: 16px 24px;
  background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.btn-generate:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(66, 153, 225, 0.4);
  background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%);
}

.btn-generate:active:not(:disabled) {
  transform: translateY(0);
}

.btn-generate:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-generate svg {
  flex-shrink: 0;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.progress-card {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.progress-header h3 {
  color: #2d3748;
  font-size: 1.1rem;
  font-weight: 600;
}

.progress-percentage {
  font-size: 1.5rem;
  font-weight: 700;
  color: #4299e1;
}

.progress-bar-container {
  margin-bottom: 16px;
}

.progress-bar {
  width: 100%;
  height: 12px;
  background: #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4299e1 0%, #48bb78 100%);
  transition: width 0.5s ease;
  border-radius: 12px;
  position: relative;
  overflow: hidden;
}

.progress-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
  animation: shine 2s infinite;
}

@keyframes shine {
  to { left: 100%; }
}

.batch-info {
  text-align: center;
  font-size: 0.95rem;
  color: #718096;
  margin-top: 12px;
}

.batch-current, .batch-total {
  font-weight: 700;
  color: #4299e1;
}

.success-message {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
  padding: 18px 24px;
  border-radius: 12px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
  animation: slideIn 0.3s ease;
  display: flex;
  align-items: center;
  gap: 12px;
}

.success-message svg {
  flex-shrink: 0;
}

.error-message {
  background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
  color: white;
  padding: 18px 24px;
  border-radius: 12px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
  animation: slideIn 0.3s ease;
  display: flex;
  align-items: center;
  gap: 12px;
}

.error-message svg {
  flex-shrink: 0;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  header h1 {
    font-size: 1.5rem;
  }
  
  .logo {
    max-width: 140px;
  }
  
  .filters-card {
    padding: 24px;
  }
  
  .filter-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .btn-generate {
    padding: 14px 20px;
    font-size: 1rem;
  }
  
  .progress-percentage {
    font-size: 1.25rem;
  }
}
</style>
