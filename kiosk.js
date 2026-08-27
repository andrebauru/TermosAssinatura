/**
 * kiosk.js – Módulo de modo quiosque avançado para o Templo TUDO TEKEM
 *
 * Funcionalidades:
 *  - Registra o Service Worker (PWA) e gerencia banner de instalação
 *  - Fullscreen persistente: bloqueia saída acidental e só permite sair via botão Sair com senha
 *  - Oculta barras de navegação do sistema e área de notificações (navigationUI: 'hide')
 *  - Bloqueia gestos de notificações nas bordas e atalhos de teclado (Keyboard Lock API)
 *  - Wake Lock API para impedir que a tela apague ou bloqueie
 *  - Bloqueia menu de contexto e ações de fuga
 */

(function () {
  'use strict';

  /* ─── 1. Service Worker / PWA ─── */
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('sw.js').catch(() => {});
    });
  }

  /* ─── 2. Banner de instalação PWA (A2HS) ─── */
  let _deferredPrompt = null;

  // Detecta se já está rodando como app instalado (standalone ou fullscreen)
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                       window.matchMedia('(display-mode: fullscreen)').matches ||
                       window.navigator.standalone === true;

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    _deferredPrompt = e;

    if (!isStandalone && !document.getElementById('pwa-install-banner')) {
      const banner = document.createElement('div');
      banner.id = 'pwa-install-banner';
      banner.innerHTML = `
        <div style="
          position: fixed; bottom: 0; left: 0; right: 0; z-index: 99999;
          background: rgba(15,5,5,0.98); border-top: 1px solid rgba(212,175,55,0.5);
          display: flex; align-items: center; justify-content: space-between;
          padding: 0.75rem 1.25rem; gap: 1rem; font-family: 'Outfit', sans-serif;
          backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
          box-shadow: 0 -4px 25px rgba(140,20,20,0.6);
        ">
          <div style="display:flex;align-items:center;gap:0.75rem;">
            <div style="width:38px;height:38px;border-radius:8px;background:rgba(212,175,55,0.15);border:1px solid #d4af37;display:flex;align-items:center;justify-content:center;color:#d4af37;font-size:1.2rem;">
              📲
            </div>
            <div>
              <div style="color:#d4af37;font-weight:700;font-size:0.9rem;">Instalar Aplicativo</div>
              <div style="color:#c8c8c8;font-size:0.78rem;">Instale para modo quiosque em tela cheia sem barras</div>
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
            ">Instalar</button>
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
            requestKioskFullscreen();
          }
        }
      });

      document.getElementById('pwa-dismiss').addEventListener('click', () => {
        banner.remove();
      });
    }
  });

  window.addEventListener('appinstalled', () => {
    _deferredPrompt = null;
    const banner = document.getElementById('pwa-install-banner');
    if (banner) banner.remove();
  });

  /* ─── 3. Fullscreen persistente e bloqueio de barras/notificações ─── */
  let _kioskEnabled = false;
  let _allowExit    = false;

  function requestKioskFullscreen() {
    if (document.fullscreenElement) return;

    const el = document.documentElement;
    const requestMethod = el.requestFullscreen ||
                          el.webkitRequestFullscreen ||
                          el.mozRequestFullScreen ||
                          el.msRequestFullscreen;

    if (requestMethod) {
      // navigationUI: 'hide' instrui o SO a ocultar a barra de navegação inferior e área de status/notificações
      const promise = requestMethod.call(el, { navigationUI: 'hide' });
      if (promise && promise.catch) {
        promise.catch(() => {});
      }
    }

    // Tenta travar orientação em retrato (portrait)
    if (screen.orientation && screen.orientation.lock) {
      screen.orientation.lock('portrait').catch(() => {});
    }

    // Tenta travar atalhos de teclado (Keyboard Lock API no Chrome/Edge)
    if (navigator.keyboard && navigator.keyboard.lock) {
      navigator.keyboard.lock(['Escape', 'F11', 'AltLeft', 'AltRight', 'Tab']).catch(() => {});
    }
  }

  // Intercepta qualquer tentativa do sistema de sair da tela cheia
  function handleFullscreenChange() {
    const isFS = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement);
    
    if (!isFS && _kioskEnabled && !_allowExit) {
      // Re-entra em tela cheia imediatamente se a saída não foi autorizada
      setTimeout(requestKioskFullscreen, 100);
    }
    
    if (isFS) {
      _kioskEnabled = true;
      _allowExit = false;
      document.body.classList.add('in-kiosk-fullscreen');
    } else {
      document.body.classList.remove('in-kiosk-fullscreen');
    }

    updateFullscreenButton();
  }

  document.addEventListener('fullscreenchange', handleFullscreenChange);
  document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
  document.addEventListener('mozfullscreenchange', handleFullscreenChange);

  // Bloqueio de atalhos de teclado
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

  // Bloqueia clique com botão direito e toque longo de menu
  document.addEventListener('contextmenu', (e) => {
    if (_kioskEnabled) e.preventDefault();
  });

  // Previne arraste acidental na borda superior (para evitar puxar painel de notificações)
  document.addEventListener('touchstart', (e) => {
    if (_kioskEnabled && e.touches && e.touches[0]) {
      const y = e.touches[0].clientY;
      // Bloqueia toque no topo extremo (onde puxa barra de notificação)
      if (y < 15) {
        e.preventDefault();
      }
    }
  }, { passive: false });

  /* ─── 4. Screen Wake Lock (mantém tela sempre acesa e focada) ─── */
  let _wakeLock = null;

  async function acquireWakeLock() {
    if (!('wakeLock' in navigator)) return;
    try {
      _wakeLock = await navigator.wakeLock.request('screen');
      _wakeLock.addEventListener('release', () => { _wakeLock = null; });
    } catch (_) {}
  }

  document.addEventListener('visibilitychange', async () => {
    if (document.visibilityState === 'visible') {
      await acquireWakeLock();
      if (_kioskEnabled && !_allowExit) {
        setTimeout(requestKioskFullscreen, 150);
      }
    }
  });

  acquireWakeLock();

  /* ─── 5. API pública para as páginas ─── */
  window.KioskMode = {
    enter() {
      _allowExit = false;
      _kioskEnabled = true;
      requestKioskFullscreen();
    },
    allowExit() {
      _allowExit = true;
    },
    exit() {
      _allowExit = true;
      _kioskEnabled = false;
      
      if (navigator.keyboard && navigator.keyboard.unlock) {
        navigator.keyboard.unlock();
      }
      
      if (document.fullscreenElement || document.webkitFullscreenElement) {
        const exitMethod = document.exitFullscreen ||
                             document.webkitExitFullscreen ||
                             document.mozCancelFullScreen ||
                             document.msExitFullscreen;
        if (exitMethod) exitMethod.call(document).catch(() => {});
      }
    },
    isActive() {
      return _kioskEnabled;
    }
  };

  /* ─── 6. Atualização visual do botão de fullscreen ─── */
  function updateFullscreenButton() {
    const btn = document.getElementById('fullscreenToggle');
    const isFS = !!(document.fullscreenElement || document.webkitFullscreenElement);
    if (btn) {
      if (isFS) {
        // Em tela cheia, o botão de tela cheia fica oculto ou desativado porque a saída só é permitida pelo botão Sair
        btn.style.display = 'none';
      } else {
        btn.style.display = 'flex';
      }
    }
  }

  // Se já abrir como app ou já em tela cheia, inicia o quiosque automaticamente
  if (isStandalone) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        window.KioskMode.enter();
      }, 500);
    });
  }

})();
