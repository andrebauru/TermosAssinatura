/**
 * kiosk.js – Módulo de modo quiosque para o Templo TUDO TEKEM
 *
 * Funcionalidades:
 *  - Registra o Service Worker (PWA)
 *  - Mostra banner de instalação PWA
 *  - Mantém fullscreen em TODAS as páginas (intercepta saídas)
 *  - Bloqueia teclas de fuga (Escape, F11, Alt+F4, etc.)
 *  - Ativa Wake Lock para manter a tela acesa e em foco
 *  - Impede abertura de menu de contexto
 */

(function () {
  'use strict';

  /* ─── 1. Service Worker / PWA ─── */
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }

  /* ─── 2. Banner de instalação (A2HS) ─── */
  let _deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    _deferredPrompt = e;

    // Cria o banner se ainda não existir
    if (!document.getElementById('pwa-install-banner')) {
      const banner = document.createElement('div');
      banner.id = 'pwa-install-banner';
      banner.innerHTML = `
        <div style="
          position: fixed; bottom: 0; left: 0; right: 0; z-index: 99999;
          background: rgba(15,5,5,0.97); border-top: 1px solid rgba(212,175,55,0.5);
          display: flex; align-items: center; justify-content: space-between;
          padding: 0.75rem 1.25rem; gap: 1rem; font-family: 'Outfit', sans-serif;
          backdrop-filter: blur(8px); box-shadow: 0 -4px 20px rgba(140,20,20,0.4);
        ">
          <div style="display:flex;align-items:center;gap:0.75rem;">
            <img src="/Files/Logo TT TEKEM.png" style="width:40px;height:40px;object-fit:contain;border-radius:8px;" onerror="this.style.display='none'">
            <div>
              <div style="color:#d4af37;font-weight:700;font-size:0.9rem;">Instalar app no tablet</div>
              <div style="color:#c8c8c8;font-size:0.78rem;">Adicione à tela inicial para modo quiosque completo</div>
            </div>
          </div>
          <div style="display:flex;gap:0.5rem;flex-shrink:0;">
            <button id="pwa-dismiss" style="
              background:transparent;border:1px solid rgba(212,175,55,0.3);
              color:#c8c8c8;border-radius:6px;padding:0.4rem 0.8rem;
              font-size:0.8rem;cursor:pointer;
            ">Agora não</button>
            <button id="pwa-install" style="
              background:linear-gradient(135deg,#8c1414,#540505);
              border:1px solid #d4af37;color:#fff;border-radius:6px;
              padding:0.4rem 1rem;font-size:0.85rem;font-weight:700;cursor:pointer;
            ">📲 Instalar</button>
          </div>
        </div>
      `;
      document.body.appendChild(banner);

      document.getElementById('pwa-install').addEventListener('click', async () => {
        if (_deferredPrompt) {
          _deferredPrompt.prompt();
          const { outcome } = await _deferredPrompt.userChoice;
          _deferredPrompt = null;
          if (outcome === 'accepted') {
            banner.remove();
            // Após instalar, pede fullscreen imediatamente
            requestKioskFullscreen();
          }
        }
      });

      document.getElementById('pwa-dismiss').addEventListener('click', () => {
        banner.remove();
      });
    }
  });

  /* ─── 3. Fullscreen persistente ─── */
  let _kioskEnabled = false;   // ativado na primeira vez que o usuário entra em FS
  let _allowExit    = false;   // permitido apenas após senha correta

  function requestKioskFullscreen() {
    if (document.fullscreenElement) return;
    document.documentElement.requestFullscreen({ navigationUI: 'hide' }).catch(() => {});
  }

  // Quando sai do fullscreen, verifica se é permitido
  document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement && _kioskEnabled && !_allowExit) {
      // Voltou a fullscreen automaticamente após um breve delay
      // (delay necessário para iOS/Android processar a mudança)
      setTimeout(requestKioskFullscreen, 150);
    }
    if (document.fullscreenElement) {
      _kioskEnabled = true;
      _allowExit = false; // reseta após re-entrar
      updateFullscreenIcons();
    }
  });

  // Bloqueio de teclas de fuga
  document.addEventListener('keydown', (e) => {
    if (!_kioskEnabled) return;

    const blocked = (
      e.key === 'Escape' ||
      e.key === 'F11' ||
      (e.altKey && e.key === 'F4') ||
      (e.ctrlKey && e.key === 'w') ||
      (e.ctrlKey && e.key === 'W') ||
      (e.metaKey && e.key === 'w') ||
      (e.metaKey && e.key === 'q')
    );

    if (blocked) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);

  // Bloqueia menu de contexto (clique direito, toque longo)
  document.addEventListener('contextmenu', (e) => {
    if (_kioskEnabled) e.preventDefault();
  });

  /* ─── 4. Wake Lock (mantém tela acesa) ─── */
  let _wakeLock = null;

  async function acquireWakeLock() {
    if (!('wakeLock' in navigator)) return;
    try {
      _wakeLock = await navigator.wakeLock.request('screen');
      _wakeLock.addEventListener('release', () => { _wakeLock = null; });
    } catch (_) {}
  }

  // Re-adquire o wake lock quando a página volta ao foco
  document.addEventListener('visibilitychange', async () => {
    if (document.visibilityState === 'visible') {
      await acquireWakeLock();
      if (_kioskEnabled && !_allowExit) {
        setTimeout(requestKioskFullscreen, 200);
      }
    }
  });

  // Inicia wake lock quando a página carrega
  acquireWakeLock();

  /* ─── 5. API pública para as páginas ─── */
  window.KioskMode = {
    /** Chame para ativar o fullscreen e o modo quiosque */
    enter() {
      requestKioskFullscreen();
      _kioskEnabled = true;
    },
    /** Chame ANTES de sair (após senha correta) */
    allowExit() {
      _allowExit = true;
    },
    /** Sai do fullscreen com permissão */
    exit() {
      _allowExit = true;
      _kioskEnabled = false;
      if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
      }
    },
    isActive() {
      return _kioskEnabled;
    }
  };

  /* ─── 6. Ícones de fullscreen (compatibilidade com index.php) ─── */
  function updateFullscreenIcons() {
    const icon = document.getElementById('fullscreenIcon');
    if (!icon) return;
    if (document.fullscreenElement) {
      icon.classList.replace('fa-expand', 'fa-compress');
    } else {
      icon.classList.replace('fa-compress', 'fa-expand');
    }
  }

})();
