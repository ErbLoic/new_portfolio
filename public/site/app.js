// Configuration de l'API
const API_BASE_URL = ''; // Symfony : même origine
const BASE_URL = '';

// Clé de cache localStorage
const CACHE_KEY = 'portfolio_data_cache';
const MESSAGES_CACHE_KEY = 'portfolio_messages_cache';
const FORMATIONS_CACHE_KEY = 'portfolio_formations_cache';
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 heures en millisecondes
const MESSAGES_CACHE_DURATION = 5 * 60 * 1000; // 5 minutes pour les messages (changent souvent)

// Cache des données
let portfolioData = null;

// ========================================
// Helper pour les images (Cloudflare R2) - Nouvelle API
// ========================================

function getImageUrl(img) {
    if (!img) return '';
    // Si c'est une string, c'est déjà l'URL
    if (typeof img === 'string') {
        if (img.startsWith('http')) return img;
        return `${img.startsWith('/') ? '' : '/'}${img}`;
    }
    // Chercher les propriétés communes pour l'URL
    const url = img.url || img.image_url || img.path || img.src || img.file || img.photo_url || '';
    if (!url) return '';
    // Si l'URL est déjà absolue (Cloudflare R2 ou autre)
    if (url.startsWith('http')) return url;
    // Sinon, ajouter le base URL
    return `${url.startsWith('/') ? '' : '/'}${url}`;
}

function getImageAlt(img, fallback) {
    if (!img) return fallback;
    if (typeof img === 'string') return fallback;
    return img.alt || img.caption || img.title || img.description || fallback;
}

// ========================================
// Gestion des erreurs API robuste
// ========================================

function handleApiError(error, context = 'API') {
    const errorMsg = error.message || 'Une erreur est survenue';
    console.error(`[${context}] Erreur:`, error);
    
    // Afficher notification discrète si possible
    if (context !== 'Photo Profil') { // Éviter le spam pour les petits éléments
        showNotification(errorMsg, 'error', 5000);
    }
    
    // Déterminer le type d'erreur
    if (error.statusCode === 404 || errorMsg.includes('404')) {
        console.warn(`[${context}] Ressource non trouvée`);
    } else if (error.statusCode === 422 || errorMsg.includes('422')) {
        console.warn(`[${context}] Données invalides`);
    } else {
        console.error(`[${context}] Erreur serveur (${error.statusCode || 500})`);
    }
}

function parseApiResponse(response) {
    // Structure API: { success: boolean, message?: string, data: any } ou { error: string }
    // Les requêtes GET retournent directement les données ou { success, message, data }
    
    // Si la réponse a un champ success explicite
    if (response && typeof response === 'object' && 'success' in response) {
        if (!response.success) {
            const error = new Error(response.error || response.message || 'Erreur API');
            error.statusCode = response.statusCode || 500;
            throw error;
        }
        return response.data;
    }
    
    // Si la réponse a un champ error
    if (response && typeof response === 'object' && 'error' in response) {
        throw new Error(response.error);
    }
    
    // Sinon, retourner directement (GET routes retournent les données directement)
    return response;
}

// ========================================
// Notifications visuelles
// ========================================

function showNotification(message, type = 'info', duration = 5000) {
    try {
        // Créer conteneur de notifications s'il n'existe pas
        let notificationContainer = document.getElementById('notification-container');
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.id = 'notification-container';
            notificationContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 400px;
            `;
            document.body.appendChild(notificationContainer);
        }

        // Créer notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
        `;

        // Styles basés sur le type
        const styles = {
            'error': {
                bg: '#FFE8E8',
                text: '#C41E3A',
                icon: '⚠️'
            },
            'success': {
                bg: '#E8F5E9',
                text: '#2E7D32',
                icon: '✅'
            },
            'info': {
                bg: '#E3F2FD',
                text: '#1565C0',
                icon: 'ℹ️'
            },
            'warning': {
                bg: '#FFF3E0',
                text: '#E65100',
                icon: '⚡'
            }
        };

        const style = styles[type] || styles['info'];
        notification.style.backgroundColor = style.bg;
        notification.style.color = style.text;
        notification.style.border = `1px solid ${style.text}30`;

        // Contenu
        notification.innerHTML = `
            <span style="font-size: 18px; flex-shrink: 0;">${style.icon}</span>
            <span style="flex: 1; font-size: 0.95rem; line-height: 1.4;">${message}</span>
            <button style="
                background: none;
                border: none;
                color: inherit;
                cursor: pointer;
                font-size: 20px;
                padding: 0;
                flex-shrink: 0;
            " onclick="this.parentElement.remove();">✕</button>
        `;

        notificationContainer.appendChild(notification);

        // Auto-remove
        if (duration > 0) {
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    } catch (e) {
        console.warn('Erreur affichage notification:', e);
    }
}

// Pagination state
let projectsPage = 0;
let realisationsPage = 0;
let allProjects = [];
let allRealisations = [];
const ITEMS_PER_PAGE = 2;
let realisationsAutoScroll = null;

// Messages carousel state
let messagesIndex = 0;
let allMessages = [];
let messagesAutoSlide = null;

// ========================================
// Système de cache localStorage
// ========================================

function getCachedData() {
    return null;
}


function setCachedData(data) {
    try {
        const cacheEntry = {
            data: data,
            timestamp: Date.now()
        };
        localStorage.setItem(CACHE_KEY, JSON.stringify(cacheEntry));
        console.log('Données mises en cache');
    } catch (error) {
        console.warn('Erreur lors de la mise en cache:', error);
    }
}

function clearCache() {
    localStorage.removeItem(CACHE_KEY);
    localStorage.removeItem(MESSAGES_CACHE_KEY);
    localStorage.removeItem(FORMATIONS_CACHE_KEY);
    console.log('Cache vidé');
}

// Fonctions génériques pour cache avec durée personnalisée
function getCachedItem(key) {
    return null;
}


function setCachedItem(key, data, duration = CACHE_DURATION) {
    try {
        const cacheEntry = {
            data: data,
            timestamp: Date.now(),
            duration: duration
        };
        localStorage.setItem(key, JSON.stringify(cacheEntry));
    } catch (error) {
        console.warn('Erreur lors de la mise en cache:', error);
    }
}

// ========================================
// Système de chargement amélioré
// ========================================

const loadingMessages = [
    { status: "Connexion au serveur...", tip: "Le serveur se réveille, cela peut prendre quelques secondes" },
    { status: "Réveil du serveur...", tip: "Les serveurs gratuits s'endorment après 15 min d'inactivité" },
    { status: "Établissement de la connexion...", tip: "Première visite ? Le chargement initial est plus long" },
    { status: "Chargement des données...", tip: "Merci de patienter, c'est bientôt prêt !" },
    { status: "Presque terminé...", tip: "Les prochaines visites seront plus rapides" },
    { status: "Finalisation...", tip: "Le serveur est maintenant actif" },
    { status: "C'est parti !", tip: "Merci pour votre patience, profitez du portfolio !" ,
    }

];

let loadingInterval = null;
let progressInterval = null;
let currentMessageIndex = 0;
let currentProgress = 0;

function updateLoadingMessage() {
    const statusEl = document.getElementById('loader-status');
    const tipEl = document.getElementById('loader-tip');
    
    if (statusEl && tipEl && currentMessageIndex < loadingMessages.length) {
        const msg = loadingMessages[currentMessageIndex];
        statusEl.style.animation = 'none';
        tipEl.style.animation = 'none';
        
        // Force reflow
        void statusEl.offsetHeight;
        void tipEl.offsetHeight;
        
        statusEl.textContent = msg.status;
        tipEl.textContent = msg.tip;
        
        statusEl.style.animation = 'fade-text 0.5s ease-out';
        tipEl.style.animation = 'fade-text 0.5s ease-out';
        
        currentMessageIndex++;
    }
}

function updateProgress(targetProgress) {
    const progressBar = document.getElementById('loader-progress-bar');
    if (progressBar) {
        currentProgress = Math.min(targetProgress, 95); // Ne jamais dépasser 95% avant la fin
        progressBar.style.width = `${currentProgress}%`;
    }
}

function startLoadingAnimation() {
    currentMessageIndex = 0;
    currentProgress = 0;
    
    // Mettre à jour les messages toutes les 4 secondes
    updateLoadingMessage();
    loadingInterval = setInterval(() => {
        updateLoadingMessage();
    }, 4000);
    
    // Simuler une progression réaliste
    progressInterval = setInterval(() => {
        // Progression plus lente au début (cold start), plus rapide ensuite
        const increment = currentProgress < 30 ? 2 : currentProgress < 60 ? 4 : 3;
        updateProgress(currentProgress + increment);
    }, 500);
}

function stopLoadingAnimation() {
    clearInterval(loadingInterval);
    clearInterval(progressInterval);
    updateProgress(100);
}

// ========================================
// Fonctions utilitaires
// ========================================

async function fetchAPI(endpoint) {
    // Les données sont injectées par Symfony dans window.PORTFOLIO_DATA (plus d'API séparée)
    const data = window.PORTFOLIO_DATA || {};
    if (endpoint === '/all') return data;
    if (endpoint === '/messages') return data.messages || [];
    if (endpoint === '/formations') return data.formations || [];
    throw new Error(`Donnée inconnue : ${endpoint}`);
}

// Ping initial pour réveiller le serveur
async function warmupServer() {
    try {
        const startTime = Date.now();
        const response = await fetch(`${API_BASE_URL}/ping`, { 
            method: 'GET',
            mode: 'cors',
            credentials: 'omit'
            // GET sans body - pas de headers pour requête SIMPLE
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        const duration = Date.now() - startTime;
        console.log(`✅ Serveur réveillé en ${duration}ms`);
        return true;
    } catch (error) {
        if (error.message.includes('Failed to fetch')) {
            console.warn('⚠️ Problème CORS au démarrage - Utilisation du cache');
        } else {
            console.warn('⚠️ Ping échoué, tentative de chargement direct...', error.message);
        }
        return false;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const months = [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];
    return `${months[date.getMonth()]} ${date.getFullYear()}`;
}

// ========================================
// Ping pour garder le serveur éveillé
// ========================================

function startPingInterval() {
    // Ping toutes les 5 minutes pour garder le serveur éveillé
    setInterval(async () => {
        try {
            const response = await fetch(`${API_BASE_URL}/ping`, {
                method: 'GET',
                mode: 'cors',
                credentials: 'omit'
                // GET sans body - pas de headers pour requête SIMPLE
            });
            
            if (response.ok) {
                console.log('✅ Ping envoyé au serveur');
            }
        } catch (error) {
            console.debug('⚠️ Erreur ping en arrière-plan (non critique)');
        }
    }, 5 * 60 * 1000); // 5 minutes
}

// ========================================
// Chargement des données
// ========================================

async function loadAllData(useCache = true) {
    // Essayer de charger depuis le cache d'abord
    if (useCache) {
        const cachedData = getCachedData();
        if (cachedData) {
            portfolioData = cachedData;
            console.log('✅ Données chargées depuis le cache local');
            return cachedData;
        }
    }
    
    // Sinon charger depuis l'API
    try {
        console.log('🔄 Chargement depuis l\'API...');
        const response = await fetchAPI('/all');
        portfolioData = response;
        
        // Mettre en cache les nouvelles données
        if (portfolioData) {
            setCachedData(portfolioData);
            console.log('✅ Données chargées depuis l\'API et mises en cache');
        }
        
        return portfolioData;
    } catch (error) {
        console.error('❌ Erreur lors du chargement depuis l\'API:', error.message);
        
        // Afficher un message d'erreur clair
        if (error.isCorsError) {
            console.error('⚠️ Problème CORS détecté - Le serveur n\'a pas configuré les en-têtes CORS');
            showNotification(
                'Impossible de charger les données du serveur. Utilisation du cache local.',
                'warning',
                6000
            );
        } else {
            handleApiError(error, 'Chargement principal');
            showNotification(
                error.message || 'Erreur lors du chargement des données',
                'error',
                6000
            );
        }
        
        // En cas d'erreur, essayer de retourner les données du cache même expirées
        const fallbackCache = localStorage.getItem(CACHE_KEY);
        if (fallbackCache) {
            try {
                const { data } = JSON.parse(fallbackCache);
                console.warn('⚠️ Utilisation du cache de secours (données possiblement anciennes)');
                return data;
            } catch (e) {
                console.error('❌ Cache de secours invalide:', e);
                return null;
            }
        }
        
        console.error('❌ Aucune donnée disponible (pas de cache)');
        return null;
    }
}

// Rafraîchir les données en arrière-plan ET mettre à jour l'UI en temps réel
async function refreshDataInBackground() {
    try {
        console.log('Rafraîchissement des données en arrière-plan...');
        const freshData = await fetchAPI('/all');
        if (freshData) {
            // Vérifier si les données ont changé avant de re-render
            const oldCache = localStorage.getItem(CACHE_KEY);
            const oldData = oldCache ? JSON.parse(oldCache).data : null;
            
            // Mettre à jour le cache principal
            setCachedData(freshData);
            
            // Mettre à jour le cache des formations si présentes dans /all
            if (freshData.formations) {
                setCachedItem(FORMATIONS_CACHE_KEY, freshData.formations, CACHE_DURATION);
            }
            
            // Mettre à jour l'UI en temps réel si les données ont changé
            if (JSON.stringify(freshData) !== JSON.stringify(oldData)) {
                console.log('Nouvelles données détectées, mise à jour de l\'interface...');
                renderAllSections(freshData);
            } else {
                console.log('Données identiques, pas de mise à jour nécessaire');
                // Quand même rafraîchir les messages (changent souvent)
                refreshMessagesInBackground();
            }
        }
    } catch (error) {
        console.warn('Rafraîchissement en arrière-plan échoué:', error);
    }
}

// ========================================
// Rendu des sections
// ========================================

function renderPortfolio(data) {
    if (!data || !data.portfolio) return;

    const portfolio = data.portfolio;

    // Hero
    document.getElementById('first-name').textContent = portfolio.first_name || 'Prénom';
    document.getElementById('last-name').textContent = portfolio.last_name || 'Nom';
    const fullName = `${portfolio.first_name || ''} ${portfolio.last_name || ''}`.trim();
    document.title = portfolio.job_title ? `${fullName} — ${portfolio.job_title}` : fullName;
    const jobEl = document.getElementById('hero-job-title');
    if (jobEl) jobEl.textContent = portfolio.job_title || '';

    // Profil
    if (portfolio.photo_url) {
        try {
            const photoProfil = document.getElementById('profil-photo');
            const imgUrl = getImageUrl(portfolio.photo_url);
            photoProfil.src = imgUrl;
            photoProfil.alt = `${portfolio.first_name || 'Profil'} ${portfolio.last_name || ''}`;
            photoProfil.loading = 'lazy';
            photoProfil.style.objectFit = 'cover';
            photoProfil.onerror = () => {
                console.warn('Erreur chargement photo profil:', imgUrl);
                photoProfil.style.opacity = '0.5';
            };
        } catch (error) {
            handleApiError(error, 'Photo Profil');
        }
    }
    document.getElementById('profil-bio').innerHTML = portfolio.bio_html || '';
    document.getElementById('promo-years').textContent = portfolio.job_title || '';

    // Stats
    const statComp = document.getElementById('stat-competences');
    if (statComp) statComp.textContent = data.competences?.length || 0;
    document.getElementById('stat-projects').textContent = data.projects?.length || 0;
    document.getElementById('stat-stages').textContent = data.stages?.length || 0;
    const techs = new Set();
    [...(data.projects || []), ...(data.realisations || []), ...(data.personalProjects || [])]
        .forEach(p => (p.tags || []).forEach(t => techs.add(t.tag.trim().toLowerCase())));
    (data.competences || []).forEach(c => techs.add(String(c.label).trim().toLowerCase()));
    const statTechs = document.getElementById('stat-techs');
    if (statTechs) statTechs.textContent = techs.size;

    // Contact
    document.getElementById('contact-message').innerHTML = portfolio.contact_message_html || '';
    document.getElementById('contact-email').textContent = portfolio.email || '';
    document.getElementById('email-cta').href = `mailto:${portfolio.email}`;

    if (portfolio.linkedin_url) {
        document.getElementById('contact-linkedin').href = portfolio.linkedin_url;
    }
    if (portfolio.github_url) {
        document.getElementById('contact-github').href = portfolio.github_url;
    }

    // CV
    const cvBtn = document.getElementById('hero-cv-btn');
    if (cvBtn && portfolio.cv_url) {
        cvBtn.href = portfolio.cv_url;
        cvBtn.style.display = '';
    }
    const cvSection = document.getElementById('cv-section');
    if (cvSection) {
        if (portfolio.cv_url) {
            document.getElementById('cv-download-btn').href = portfolio.cv_url;
            cvSection.style.display = '';
        } else {
            cvSection.style.display = 'none';
        }
    }

    // Footer
    // Pied de page rendu côté serveur (nom, titre, mentions légales)
}

const escHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const LEVEL_LABELS = ['', 'Notions', 'Débutant', 'Intermédiaire', 'Avancé', 'Expert'];

function renderCompetences(competences, certifications) {
    const section = document.getElementById('competences');
    const hasComp = competences && competences.length;
    const hasCert = certifications && certifications.length;
    if (!section) return;
    section.classList.toggle('hidden', !hasComp && !hasCert);
    document.querySelectorAll('[href="#competences"]').forEach((a) => a.closest('li, a').classList.toggle('hidden', !hasComp && !hasCert));
    renderCertifications(certifications);
    const container = document.getElementById('competences-grid');
    container.innerHTML = '';
    if (!hasComp) return;

    // Grouper par bloc
    const blocs = {};
    competences.forEach(comp => {
        if (!blocs[comp.bloc]) {
            blocs[comp.bloc] = [];
        }
        blocs[comp.bloc].push(comp);
    });

    // Générer le HTML pour chaque bloc
    Object.keys(blocs).sort().forEach(bloc => {
        const comps = blocs[bloc];
        const level = (n) => Math.min(5, Math.max(1, parseInt(n, 10) || 3));

        const blocHtml = `
            <div class="competence-bloc">
                <h3 class="bloc-title">${escHtml(bloc)}</h3>
                <ul class="skill-list">
                    ${comps.map(comp => `
                        <li class="skill-item" title="${escHtml(comp.description || '')}">
                            <span class="skill-name">${comp.icon ? `<i class="bi ${escHtml(comp.icon)}" aria-hidden="true"></i>` : ''}${escHtml(comp.label)}</span>
                            <span class="skill-level" role="img" aria-label="Niveau ${level(comp.level)} sur 5 : ${LEVEL_LABELS[level(comp.level)]}">
                                ${[1, 2, 3, 4, 5].map(i => `<span class="skill-pip${i <= level(comp.level) ? ' on' : ''}"></span>`).join('')}
                            </span>
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;

        container.innerHTML += blocHtml;
    });
}

function renderCertifications(certifications) {
    const box = document.getElementById('certifications-list');
    const wrap = document.querySelector('.certifications-block');
    if (!box || !wrap) return;
    wrap.classList.toggle('hidden', !certifications || !certifications.length);
    if (!certifications || !certifications.length) return;
    const today = new Date().toISOString().slice(0, 10);
    box.innerHTML = certifications.map(c => {
        const expired = c.expires_at && c.expires_at < today;
        const inner = `
            <span class="cert-badge">${c.badge_url ? `<img src="${escHtml(getImageUrl(c.badge_url))}" alt="" loading="lazy">` : '<i class="bi bi-patch-check" aria-hidden="true"></i>'}</span>
            <span class="cert-text">
                <strong>${escHtml(c.name)}</strong>
                <span>${escHtml(c.issuer)}${c.issued_at ? ' · ' + formatDate(c.issued_at) : ''}${expired ? ' · <em>expirée</em>' : ''}</span>
            </span>
            ${c.credential_url ? '<i class="bi bi-box-arrow-up-right cert-link-ico" aria-hidden="true"></i>' : ''}`;
        return c.credential_url
            ? `<a class="cert-card" href="${escHtml(c.credential_url)}" target="_blank" rel="noopener" aria-label="${escHtml(c.name)} — vérifier la certification">${inner}</a>`
            : `<div class="cert-card">${inner}</div>`;
    }).join('');
}

function renderMissions(missions) {
    if (!missions || !missions.length) return '';
    return `
        <div class="missions">
            <p class="missions-title"><i class="bi bi-list-check" aria-hidden="true"></i> Mes missions</p>
            ${missions.map(m => `
                <details class="mission">
                    <summary>${escHtml(m.title)}</summary>
                    <div class="mission-body md">
                        ${m.context_html ? `<p class="mission-step">Contexte</p>${m.context_html}` : ''}
                        ${m.actions_html ? `<p class="mission-step">Ce que j'ai fait</p>${m.actions_html}` : ''}
                        ${m.results_html ? `<p class="mission-step">Résultats</p>${m.results_html}` : ''}
                        ${m.tags?.length ? `<div class="mission-tags">${m.tags.map(t => `<span>${escHtml(t)}</span>`).join('')}</div>` : ''}
                    </div>
                </details>
            `).join('')}
        </div>`;
}

/** Éléments de frise pour les expériences : { start, current, html(isRight) }. */
function stageTimelineItems(stages) {
    // Type d'expérience : stage, alternance ou job
    const EXPERIENCE_TYPES = { stage: 'Stage', alternance: 'Alternance', job: 'Job' };

    return (stages || []).map(stage => ({ start: stage.start_date, current: stage.is_current, html: (isRight) => {
        const typeLabel = EXPERIENCE_TYPES[stage.type] || 'Stage';
        const period = `${formatDate(stage.start_date)} — ${stage.is_current ? 'En cours' : formatDate(stage.end_date)}`;
        const rightBadge = stage.is_current
            ? `<span class="formation-current-badge"><span class="formation-current-dot"></span> En cours</span>`
            : (stage.duration ? `<span class="stage-duration">${stage.duration}</span>` : '');

        const companyName = stage.company?.name || 'Entreprise';
        const companyInitial = companyName.charAt(0).toUpperCase();
        const logoInner = stage.company?.photo_url
            ? `<img src="${getImageUrl(stage.company.photo_url)}" alt="${companyName}" loading="lazy"
                    onerror="this.style.display='none';this.parentElement.dataset.fallback='${companyInitial}';this.parentElement.classList.add('stage-logo-col--fallback');" />`
            : ``;
        const logoColClass = stage.company?.photo_url ? '' : 'stage-logo-col--fallback';

        const stageHtml = `
            <div class="timeline-item ${isRight ? 'right' : ''}${stage.is_current ? ' is-current' : ''}">
                <div class="timeline-dot"></div>
                <div class="stage-card" ${isRight ? 'style="grid-column: 2;"' : ''}>
                    <div class="stage-card-inner">
                        <div class="stage-logo-col ${logoColClass}" data-fallback="${companyInitial}">
                            ${logoInner}
                        </div>
                        <div class="stage-content">
                            <div class="stage-content-top">
                                <div>
                                    <h4 class="stage-company">${companyName}</h4>
                                    ${stage.company?.sector ? `<p class="stage-sector">${stage.company.sector}</p>` : ''}
                                </div>
                                <span class="stage-work-badge">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                                    ${typeLabel}
                                </span>
                            </div>
                            <div class="stage-divider"></div>
                            ${stage.role ? `<p class="stage-role">${stage.role}</p>` : ''}
                            <div class="stage-meta-row">
                                <p class="stage-dates">${period}</p>
                                ${rightBadge}
                            </div>
                            ${stage.formation ? `<p class="via"><i class="bi bi-mortarboard" aria-hidden="true"></i> ${escHtml(EXPERIENCE_TYPES[stage.type] || 'Stage')} réalisé${stage.type === 'alternance' ? 'e' : ''} dans le cadre de <strong>${escHtml(stage.formation.title)}</strong>${stage.formation.school ? ` · ${escHtml(stage.formation.school)}` : ''}</p>` : ''}
                            ${stage.reason_html ? `<div class="why"><p class="why-label"><i class="bi bi-lightbulb" aria-hidden="true"></i> Pourquoi ce choix ?</p><div class="why-text md">${stage.reason_html}</div></div>` : ''}
                            ${stage.description_html ? `<div class="stage-description md">${stage.description_html}</div>` : ''}
                            ${renderMissions(stage.missions)}
                        </div>
                    </div>
                </div>
                ${isRight ? '' : '<div></div>'}
            </div>
        `;

        return stageHtml;
    }}));
}

/** Éléments de frise pour les formations : { start, current, html(isRight) }. */
function formationTimelineItems(formations) {
    return (formations || []).map(formation => ({ start: formation.start_date, current: formation.is_current, html: (isRight) => {
        const period = formation.period || `${formatDate(formation.start_date)} — ${formation.is_current ? 'En cours' : formatDate(formation.end_date)}`;
        const schoolName = formation.school || 'École';
        const schoolInitial = schoolName.charAt(0).toUpperCase();

        const logoInner = formation.logo_url
            ? `<img src="${getImageUrl(formation.logo_url)}" alt="${schoolName}" loading="lazy"
                    onerror="this.style.display='none';this.parentElement.dataset.fallback='${schoolInitial}';this.parentElement.classList.add('form-logo-col--fallback');" />`
            : ``;
        const logoColClass = formation.logo_url ? '' : 'form-logo-col--fallback';

        const rightBadge = formation.is_current
            ? `<span class="formation-current-badge"><span class="formation-current-dot"></span> En cours</span>`
            : (formation.degree_type ? `<span class="formation-degree-badge">${formation.degree_type}</span>` : '');

        const formationHtml = `
            <div class="timeline-item formation ${isRight ? 'right' : ''}${formation.is_current ? ' is-current' : ''}">
                <div class="timeline-dot formation-dot"></div>
                <div class="formation-card-v2" ${isRight ? 'style="grid-column: 2;"' : ''}>
                    <div class="stage-card-inner">
                        <div class="form-logo-col ${logoColClass}" data-fallback="${schoolInitial}">
                            ${logoInner}
                        </div>
                        <div class="stage-content">
                            <div class="stage-content-top">
                                <div>
                                    <h4 class="stage-company">${formation.title || 'Formation'}</h4>
                                    <p class="form-school">${schoolName}${formation.location ? ' — ' + formation.location : ''}</p>
                                </div>
                                <span class="formation-badge">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                                    Formation
                                </span>
                            </div>
                            <div class="stage-divider form-divider"></div>
                            <div class="stage-meta-row">
                                <p class="formation-dates">${period}</p>
                                ${rightBadge}
                            </div>
                            ${formation.reason_html ? `<div class="why"><p class="why-label"><i class="bi bi-lightbulb" aria-hidden="true"></i> Pourquoi ce choix ?</p><div class="why-text md">${formation.reason_html}</div></div>` : ''}
                            ${formation.description_html ? `<div class="stage-description md">${formation.description_html}</div>` : ''}
                        </div>
                    </div>
                </div>
                ${isRight ? '' : '<div></div>'}
            </div>
        `;

        return formationHtml;
    }}));
}


/* ---------- Frise fusionnée : deux couloirs (formations | expériences) groupés par année ---------- */
const PM_TYPES = { stage: 'Stage', alternance: 'Alternance', job: 'Job' };

function pmCard(kind, o) {
    const isF = kind === 'formation';
    const title = isF ? (o.title || 'Formation') : (o.role || 'Expérience');
    const sub = isF ? [o.school, o.location].filter(Boolean).join(' — ') : (o.company?.name || '');
    const logo = isF ? o.logo_url : o.company?.photo_url;
    const initial = (sub || title).charAt(0).toUpperCase();
    const period = `${formatDate(o.start_date)} — ${o.is_current ? 'aujourd’hui' : formatDate(o.end_date)}`;
    const badge = isF ? (o.degree_type || 'Formation') : (PM_TYPES[o.type] || 'Stage');
    return `
        <article class="pm-card pm-${kind}${o.is_current ? ' is-current' : ''}">
            <div class="pm-card-head">
                <span class="pm-logo">${logo ? `<img src="${escHtml(getImageUrl(logo))}" alt="" loading="lazy" onerror="this.remove()">` : ''}<span>${escHtml(initial)}</span></span>
                <div class="pm-card-titles">
                    <h4>${escHtml(title)}</h4>
                    ${sub ? `<p class="pm-sub">${escHtml(sub)}</p>` : ''}
                </div>
            </div>
            <div class="pm-meta">
                <span class="pm-badge"><i class="bi ${isF ? 'bi-mortarboard' : 'bi-briefcase'}" aria-hidden="true"></i>${escHtml(badge)}</span>
                <span class="pm-dates">${period}</span>
                ${o.is_current ? '<span class="pm-now"><span></span>En cours</span>' : ''}
            </div>
            ${isF ? pmLinkedStages(o) : (o.formation ? `<p class="via"><i class="bi bi-mortarboard" aria-hidden="true"></i> ${escHtml(PM_TYPES[o.type] || 'Stage')} réalisé${o.type === 'alternance' ? 'e' : ''} dans le cadre de <strong>${escHtml(o.formation.title)}</strong>${o.formation.school ? ` · ${escHtml(o.formation.school)}` : ''}</p>` : '')}
            ${o.reason_html ? `<div class="why"><p class="why-label"><i class="bi bi-lightbulb" aria-hidden="true"></i> Pourquoi ce choix ?</p><div class="why-text md">${o.reason_html}</div></div>` : ''}
            ${o.description_html ? `<div class="pm-desc md">${o.description_html}</div>` : ''}
            ${isF ? '' : renderMissions(o.missions)}
        </article>`;
}

let pmAllStages = [];
/** Côté formation : rappelle les stages / alternances qu'elle a demandés. */
function pmLinkedStages(f) {
    const linked = pmAllStages.filter(s => s.formation_id === f.id);
    if (!linked.length) return '';
    return `<p class="via via-f"><i class="bi bi-link-45deg" aria-hidden="true"></i> ${linked.map(s =>
        `${escHtml(PM_TYPES[s.type] || 'Stage')} : <strong>${escHtml(s.role)}</strong>${s.company?.name ? ` chez ${escHtml(s.company.name)}` : ''}`).join(' · ')}</p>`;
}

function renderMergedTimeline(container, formations, stages, portfolio = {}) {
    pmAllStages = stages;
    const year = (o) => (o.start_date ? new Date(o.start_date).getFullYear() : 0);
    const byDate = (a, b) => (b.is_current - a.is_current) || (new Date(b.start_date || 0) - new Date(a.start_date || 0));
    const years = [...new Set([...formations, ...stages].map(year))].sort((a, b) => b - a);

    container.innerHTML = `
        <div class="pm" data-pm>
            <div class="pm-legend" aria-hidden="true">
                <span class="pm-legend-f"><i class="bi bi-mortarboard"></i> Formations</span>
                <span class="pm-legend-x"><i class="bi bi-briefcase"></i> Expériences</span>
            </div>
            <div class="pm-axis" aria-hidden="true"><span class="pm-axis-fill"></span></div>
            ${portfolio.timeline_next_html ? `
                <div class="pm-end pm-next">
                    <span class="pm-end-dot"><i class="bi bi-flag" aria-hidden="true"></i></span>
                    <div class="pm-end-card"><p class="pm-end-label">Et ensuite ?</p><div class="md">${portfolio.timeline_next_html}</div></div>
                </div>` : ''}
            ${years.map(y => {
                const f = formations.filter(o => year(o) === y).sort(byDate);
                const x = stages.filter(o => year(o) === y).sort(byDate);
                return `
                    <div class="pm-year"><span>${y || '—'}</span></div>
                    <div class="pm-row">
                        <div class="pm-lane pm-lane-f">${f.map(o => pmCard('formation', o)).join('')}</div>
                        <div class="pm-lane pm-lane-x">${x.map(o => pmCard('stage', o)).join('')}</div>
                    </div>`;
            }).join('')}
            ${portfolio.timeline_start_html ? `
                <div class="pm-end pm-start">
                    <span class="pm-end-dot"><i class="bi bi-rocket-takeoff" aria-hidden="true"></i></span>
                    <div class="pm-end-card"><p class="pm-end-label">Le point de départ</p><div class="md">${portfolio.timeline_start_html}</div></div>
                </div>` : ''}
        </div>`;
    initTimelineProgress();
}

/** L'axe central se remplit au fil du défilement. */
let pmProgressBound = false;
function initTimelineProgress() {
    if (pmProgressBound) return;
    pmProgressBound = true;
    let ticking = false;
    const update = () => {
        ticking = false;
        document.querySelectorAll('[data-pm]').forEach(pm => {
            const r = pm.getBoundingClientRect();
            const p = Math.min(1, Math.max(0, (window.innerHeight * 0.65 - r.top) / r.height));
            pm.style.setProperty('--pm-progress', p.toFixed(3));
        });
    };
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener('resize', update);
    update();
}

/** Remplit une frise : éléments en cours d'abord, puis du plus récent au plus ancien. */
function fillTimeline(container, items) {
    if (!container) return;
    items.sort((a, b) => (b.current - a.current) || (new Date(b.start || 0) - new Date(a.start || 0)));
    container.innerHTML = items.map((it, i) => it.html(i % 2 === 1)).join('');
}

/**
 * Parcours, selon le choix fait dans l'admin (Profil → Parcours) :
 * - « split »  : formations dans « Profil », expériences dans « Mes expériences » ;
 * - « merged » : formations et expériences sur une seule frise dans « Profil ».
 */
function renderParcours(data) {
    const merged = data.portfolio?.timeline_mode === 'merged';
    const formations = formationTimelineItems(data.formations);
    const stages = stageTimelineItems(data.stages);
    const parcoursItems = merged ? [...formations, ...stages] : formations;

    document.querySelector('.parcours-section')?.classList.toggle('hidden', !parcoursItems.length);
    const parcours = document.getElementById('parcours-timeline');
    parcours?.classList.toggle('timeline', !merged);
    if (merged && parcours) renderMergedTimeline(parcours, data.formations || [], data.stages || [], data.portfolio || {});
    else fillTimeline(parcours, parcoursItems);

    const showStages = !merged && stages.length > 0;
    document.querySelector('.stages-section')?.classList.toggle('hidden', !showStages);
    const stagesContainer = document.getElementById('stages-timeline');
    if (showStages) fillTimeline(stagesContainer, stages);
    else if (stagesContainer) stagesContainer.innerHTML = '';
}

function renderRealisations(data) {
    if (!data) return;

    const container = document.getElementById('realisations-by-company');
    container.innerHTML = '';

    // Collecte toutes les réalisations
    let allRealisationsOnly = [];
    if (data.realisations && data.companies && data.realisationsByCompany) {
        const realisationsByCompany = data.realisationsByCompany || {};
        data.companies.forEach(company => {
            const companyRealisations = realisationsByCompany[company.id];
            if (companyRealisations && companyRealisations.length) {
                companyRealisations.forEach(real => {
                    allRealisationsOnly.push({ ...real, company, itemType: 'realisation' });
                });
            }
        });
    }

    // Collecte tous les projets
    let allProjectsOnly = [];
    if (data.projects && Array.isArray(data.projects)) {
        data.projects.forEach(projet => {
            allProjectsOnly.push({ ...projet, itemType: 'project' });
        });
    }

    // Deux colonnes, chacune du plus récent au plus ancien (« sort_key » = « AAAA-MM ») :
    //   à gauche  → entreprise : réalisations + projets rattachés à une entreprise
    //   à droite  → école      : projets avec une école renseignée (ou sans entreprise)
    const byDate = (a, b) => (b.sort_key || '').localeCompare(a.sort_key || '') || (b.id || 0) - (a.id || 0);
    const isSchool = (p) => !!p.school || !p.company; // école renseignée dans l'admin, ou aucune entreprise
    const companyItems = [...allRealisationsOnly, ...allProjectsOnly.filter(p => !isSchool(p))].sort(byDate);
    const schoolItems = allProjectsOnly.filter(isSchool).sort(byDate);

    // Une page = [entreprise, école] tant que les deux colonnes ont des projets ;
    // ensuite les projets restants (d'un seul type) remplissent les deux cases : pas de page à moitié vide.
    allRealisations = [];
    const pairs = Math.min(companyItems.length, schoolItems.length);
    for (let i = 0; i < pairs; i++) {
        allRealisations.push(companyItems[i], schoolItems[i]);
    }
    allRealisations.push(...companyItems.slice(pairs), ...schoolItems.slice(pairs));

    // Reset la page et affiche
    realisationsPage = 0;
    renderRealisationsPage();
    initRealisationsPagination();
}

function renderRealisationsPage() {
    const container = document.getElementById('realisations-by-company');
    container.innerHTML = '';
    
    const start = realisationsPage * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const pageItems = allRealisations.slice(start, end);
    
    const typeColors = {
        'stage': '#000DFF',
        'projet': '#6B73FF',
    };
    
    pageItems.forEach(item => {
        // Case vide : garde « entreprise à gauche, école à droite » quand une colonne est plus courte
        if (item.itemType === 'empty') {
            container.innerHTML += '<div class="realisation-placeholder" aria-hidden="true"></div>';
            return;
        }
        const isProject = item.itemType === 'project';
        const href = isProject ? `/projet/${item.id}` : `/realisation/${item.id}`;
        
        // Déterminer la couleur
        let bgColor;
        if (isProject) {
            const projectTypeColors = {
                'Application Web': '#0EA5E9',
                'Web': '#0EA5E9',
                'Application Mobile': '#A855F7',
                'Flutter': '#06B6D4',
                'Script & Automatisation': '#F59E0B',
                'Application C#': '#EF4444',
                'Base de données': '#10B981',
                'BDD': '#10B981',
            };
            const colorIndex = item.id ? parseInt(item.id.toString()) : 0;
            const fallbackColors = ['#7C3AED', '#EC4899', '#6366F1', '#14B8A6', '#FB923C', '#84CC16', '#F43F5E', '#22D3EE'];
            bgColor = projectTypeColors[item.type] || fallbackColors[colorIndex % fallbackColors.length];
        } else {
            const colorIndex = item.id ? parseInt(item.id.toString()) : 0;
            const colors = ['#7C3AED', '#0EA5E9', '#F59E0B', '#10B981', '#EF4444', '#EC4899', '#6366F1', '#14B8A6', '#F43F5E', '#8B5CF6', '#06B6D4', '#84CC16', '#FB923C', '#A855F7', '#22D3EE', '#4ADE80'];
            bgColor = colors[colorIndex % colors.length];
        }
        
        const realHtml = `
            <a href="${href}" class="realisation-card-link">
                <div class="realisation-card">
                    <div class="realisation-image" style="background: linear-gradient(135deg, ${bgColor}15, ${bgColor}05);">
                    <div class="project-decoration-1" style="background: ${bgColor};"></div>
                    <div class="project-decoration-2" style="background: ${bgColor};"></div>

                    ${item.company?.photo_url ? `
                        <img src="${getImageUrl(item.company.photo_url)}" 
                             alt="${item.company?.name || ''}" 
                             class="realisation-image-logo" 
                             loading="lazy"
                             onerror="this.style.display='none'; this.closest('.realisation-image').querySelector('.project-icon').style.display='flex';" />
                        <div class="project-icon" style="background: ${bgColor}; box-shadow: 0 8px 24px ${bgColor}40; display: none;">
                    ` : `
                        <div class="project-icon" style="background: ${bgColor}; box-shadow: 0 8px 24px ${bgColor}40;">
                    `}
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                                <path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3M3 16v3a2 2 0 002 2h3m8 0h3a2 2 0 002-2v-3" stroke="white" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </div>
                    
                    ${item.company?.name ? `
                        <div class="realisation-company-label" style="background: ${bgColor};">
                            ${item.company.name}
                        </div>
                    ` : ''}
                </div>
                    <div class="realisation-content">
                        <span class="realisation-type" style="color: ${bgColor}; background: ${bgColor}12;">${isProject ? item.type : (item.type === 'stage' ? 'Stage' : 'Réalisation')}${item.date_label ? ' · ' + item.date_label : ''}</span>
                        <h4 class="realisation-title">${item.title}</h4>
                        <p class="realisation-description">${item.description_inline || ''}</p>
                        ${item.tags && item.tags.length ? `
                            <div class="realisation-tags">
                                ${item.tags.map(tag => `<span class="tag">${tag.tag}</span>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                    <span class="card-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </a>
        `;
        container.innerHTML += realHtml;
    });
    
    updateRealisationsDots();
    updateRealisationsArrows();
}

function startRealisationsAutoScroll() {
    stopRealisationsAutoScroll();
    const totalPages = Math.ceil(allRealisations.length / ITEMS_PER_PAGE);
    if (totalPages <= 1) return;
    realisationsAutoScroll = setInterval(() => {
        realisationsPage = (realisationsPage + 1) % totalPages;
        renderRealisationsPage();
    }, 30000);
}

function stopRealisationsAutoScroll() {
    if (realisationsAutoScroll) {
        clearInterval(realisationsAutoScroll);
        realisationsAutoScroll = null;
    }
}

function initRealisationsPagination() {
    const prevBtn = document.getElementById('realisations-prev');
    const nextBtn = document.getElementById('realisations-next');
    const dotsContainer = document.getElementById('realisations-dots');
    const carousel = document.getElementById('realisations-by-company')?.closest('.paginated-container');

    const totalPages = Math.ceil(allRealisations.length / ITEMS_PER_PAGE);

    // Créer les dots
    dotsContainer.innerHTML = '';
    for (let i = 0; i < totalPages; i++) {
        const dot = document.createElement('button');
        dot.className = `pagination-dot ${i === 0 ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Page ${i + 1}`);
        dot.addEventListener('click', () => {
            realisationsPage = i;
            renderRealisationsPage();
            startRealisationsAutoScroll();
        });
        dotsContainer.appendChild(dot);
    }

    // Event listeners pour les flèches
    prevBtn.onclick = () => {
        if (realisationsPage > 0) {
            realisationsPage--;
            renderRealisationsPage();
            startRealisationsAutoScroll();
        }
    };

    nextBtn.onclick = () => {
        if (realisationsPage < totalPages - 1) {
            realisationsPage++;
            renderRealisationsPage();
            startRealisationsAutoScroll();
        }
    };

    // Pause au hover
    if (carousel) {
        carousel.addEventListener('mouseenter', stopRealisationsAutoScroll);
        carousel.addEventListener('mouseleave', startRealisationsAutoScroll);
    }

    updateRealisationsArrows();
    startRealisationsAutoScroll();
}

function updateRealisationsDots() {
    const dots = document.querySelectorAll('#realisations-dots .pagination-dot');
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === realisationsPage);
    });
}

function updateRealisationsArrows() {
    const prevBtn = document.getElementById('realisations-prev');
    const nextBtn = document.getElementById('realisations-next');
    const totalPages = Math.ceil(allRealisations.length / ITEMS_PER_PAGE);
    
    prevBtn.disabled = realisationsPage === 0;
    nextBtn.disabled = realisationsPage >= totalPages - 1;
    
    // Masquer si une seule page
    const container = document.querySelector('#realisations-by-company').closest('.paginated-container');
    const dotsContainer = document.getElementById('realisations-dots');
    if (totalPages <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
        dotsContainer.style.display = 'none';
    } else {
        prevBtn.style.display = '';
        nextBtn.style.display = '';
        dotsContainer.style.display = '';
    }
}

function updateCurrentCompanyInfo() {
    // Récupérer la première réalisation de la page actuelle
    const start = realisationsPage * ITEMS_PER_PAGE;
    const firstItem = allRealisations[start];
    
    if (!firstItem || !firstItem.company) {
        // Fallback si pas de company
        document.getElementById('company-logo-img').src = '';
        document.getElementById('company-name').textContent = 'Entreprise';
        document.getElementById('company-sector').textContent = '';
        document.getElementById('company-location').textContent = '';
        document.getElementById('company-description').textContent = '';
        return;
    }
    
    const company = firstItem.company;
    
    // Mettre à jour le logo
    const logoImg = document.getElementById('company-logo-img');
    if (company.photo_url) {
        logoImg.src = getImageUrl(company.photo_url);
        logoImg.alt = company.name;
    } else {
        logoImg.src = '';
    }
    
    // Mettre à jour les infos
    document.getElementById('company-name').textContent = company.name || 'Entreprise';
    document.getElementById('company-sector').textContent = company.sector || '';
    document.getElementById('company-location').textContent = company.location ? `📍 ${company.location}` : '';
    document.getElementById('company-description').innerHTML = company.description_html || '';
}

function renderProjects(projects) {
    if (!projects || !projects.length) return;

    // Trier les projets par année (le plus récent en premier)
    allProjects = [...projects].sort((a, b) => {
        const yearA = parseInt(a.year) || 0;
        const yearB = parseInt(b.year) || 0;
        return yearB - yearA;
    });
    projectsPage = 0;
    renderProjectsPage();
    initProjectsPagination();
}

function renderProjectsPage() {
    const container = document.getElementById('projects-grid');
    container.innerHTML = '';

    const typeColors = {
        'Application Web': '#000DFF',
        'Application Mobile': '#6B73FF',
        'Script & Automatisation': '#3b44d1',
        'Base de données': '#8b92ff',
    };

    const start = projectsPage * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const pageItems = allProjects.slice(start, end);

    pageItems.forEach(projet => {
        const color = typeColors[projet.type] || '#6B73FF';

        const projectHtml = `
    <a href="/projet/${projet.id}" class="project-card-link">
        <div class="project-card">
            <div class="project-image" style="background: linear-gradient(135deg, ${color}15, ${color}05);">
                <div class="project-decoration-1" style="background: ${color};"></div>
                <div class="project-decoration-2" style="background: ${color};"></div>

                ${projet.company?.photo_url ? `
                    <img src="${getImageUrl(projet.company.photo_url)}" 
                         alt="${projet.company?.name || ''}" 
                         class="project-image-logo"
                         loading="lazy"
                         onerror="this.style.display='none'; this.closest('.project-image').querySelector('.project-icon').style.display='flex';" />
                    <div class="project-icon" style="background: ${color}; box-shadow: 0 8px 24px ${color}40; display: none;">
                ` : `
                    <div class="project-icon" style="background: ${color}; box-shadow: 0 8px 24px ${color}40;">
                `}
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3M3 16v3a2 2 0 002 2h3m8 0h3a2 2 0 002-2v-3" stroke="white" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </div>

                <span class="project-year" style="color: ${color};">${projet.year}</span>
            </div>
            <div class="project-content">
                <span class="project-type" style="color: ${color}; background: ${color}12; border: 1px solid ${color}25;">${projet.type}</span>
                <h3 class="project-title">${projet.title}</h3>
                <p class="project-description">${projet.description_inline || ''}</p>
                ${projet.tags && projet.tags.length ? `
                    <div class="project-tags">
                        ${projet.tags.map(tag => `<span class="tag">${tag.tag}</span>`).join('')}
                    </div>
                ` : ''}
            </div>
            <span class="card-arrow">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </span>
        </div>
    </a>
`;

        container.innerHTML += projectHtml;
    });
    
    updateProjectsDots();
    updateProjectsArrows();
}

function initProjectsPagination() {
    const prevBtn = document.getElementById('projects-prev');
    const nextBtn = document.getElementById('projects-next');
    const dotsContainer = document.getElementById('projects-dots');
    
    const totalPages = Math.ceil(allProjects.length / ITEMS_PER_PAGE);
    
    // Créer les dots
    dotsContainer.innerHTML = '';
    for (let i = 0; i < totalPages; i++) {
        const dot = document.createElement('button');
        dot.className = `pagination-dot ${i === 0 ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Page ${i + 1}`);
        dot.addEventListener('click', () => {
            projectsPage = i;
            renderProjectsPage();
        });
        dotsContainer.appendChild(dot);
    }
    
    // Event listeners pour les flèches
    prevBtn.onclick = () => {
        if (projectsPage > 0) {
            projectsPage--;
            renderProjectsPage();
        }
    };
    
    nextBtn.onclick = () => {
        const totalPages = Math.ceil(allProjects.length / ITEMS_PER_PAGE);
        if (projectsPage < totalPages - 1) {
            projectsPage++;
            renderProjectsPage();
        }
    };
    
    updateProjectsArrows();
}

function updateProjectsDots() {
    const dots = document.querySelectorAll('#projects-dots .pagination-dot');
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === projectsPage);
    });
}

function updateProjectsArrows() {
    const prevBtn = document.getElementById('projects-prev');
    const nextBtn = document.getElementById('projects-next');
    const totalPages = Math.ceil(allProjects.length / ITEMS_PER_PAGE);
    
    prevBtn.disabled = projectsPage === 0;
    nextBtn.disabled = projectsPage >= totalPages - 1;
    
    // Masquer si une seule page
    const dotsContainer = document.getElementById('projects-dots');
    if (totalPages <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
        dotsContainer.style.display = 'none';
    } else {
        prevBtn.style.display = '';
        nextBtn.style.display = '';
        dotsContainer.style.display = '';
    }
}

// ========================================
// Navigation
// ========================================

function initNavbar() {
    const navbar = document.getElementById('navbar');
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobile-menu');

    // Gestion du scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 40) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Gestion du menu mobile
    hamburger.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });

    // Fermer le menu mobile au clic sur un lien
    document.querySelectorAll('.nav-mobile-link').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
        });
    });
}

// ========================================
// Initialisation
// ========================================

async function renderAllSections(data) {
    if (!data) return false;
    
    renderPortfolio(data);
    renderParcours(data);
    renderCompetences(data.competences, data.certifications);
    renderRealisations(data);
    renderPersonalProjects(data.personalProjects);
    // renderProjects n'est plus appelé - fusionné avec renderRealisations
    
    // Charger les messages (cache court car changent souvent)
    const cachedMessages = getCachedItem(MESSAGES_CACHE_KEY);
    if (cachedMessages) {
        renderMessages(cachedMessages);
        // Rafraîchir en arrière-plan quand même
        refreshMessagesInBackground();
    } else {
        try {
            const response = await fetchAPI('/messages');
            const messages = response?.data || response;
            setCachedItem(MESSAGES_CACHE_KEY, messages, MESSAGES_CACHE_DURATION);
            renderMessages(messages);
        } catch (error) {
            console.warn('Erreur lors du chargement des messages:', error);
        }
    }
    
    // Animer les nouveaux éléments rendus
    setTimeout(refreshScrollAnimations, 50);

    return true;
}

// Rafraîchir les messages en arrière-plan
async function refreshMessagesInBackground() {
    try {
        const response = await fetchAPI('/messages');
        const messages = response?.data || response;
        const oldMessages = getCachedItem(MESSAGES_CACHE_KEY);
        
        if (JSON.stringify(messages) !== JSON.stringify(oldMessages)) {
            console.log('Nouveaux messages détectés, mise à jour...');
            setCachedItem(MESSAGES_CACHE_KEY, messages, MESSAGES_CACHE_DURATION);
            renderMessages(messages);
        }
    } catch (error) {
        // Silencieux en arrière-plan
    }
}

// ========================================
// Messages Carousel
// ========================================

function renderMessages(messages) {
    const section = document.getElementById('messages-section');
    const carousel = document.getElementById('messages-carousel');
    const nav = document.getElementById('messages-nav');
    
    if (!messages || !messages.length) {
        section.classList.add('hidden');
        return;
    }
    
    // Filtrer les messages actifs (l'API peut déjà le faire, mais double vérification)
    allMessages = messages.filter(msg => msg.is_active);
    
    if (!allMessages.length) {
        section.classList.add('hidden');
        return;
    }
    
    section.classList.remove('hidden');
    carousel.innerHTML = '';
    
    // Icône par défaut selon le type si pas d'icône spécifiée
    const defaultIcons = {
        'urgent': 'exclamation-triangle-fill',
        'warning': 'exclamation-circle-fill',
        'success': 'check-circle-fill',
        'info': 'info-circle-fill'
    };
    
    // Types avec couleurs
    const typeClasses = {
        'urgent': 'message-urgent',
        'warning': 'message-warning',
        'success': 'message-success',
        'info': 'message-info'
    };
    
    allMessages.forEach((msg, index) => {
        const typeClass = typeClasses[msg.type] || 'message-info';
        // Utiliser l'icône Bootstrap spécifiée ou l'icône par défaut du type
        const iconName = msg.icon || defaultIcons[msg.type] || 'info-circle';
        
        const messageHtml = `
            <div class="message-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                <div class="message-card ${typeClass}">
                    <div class="message-icon"><i class="bi bi-${iconName}"></i></div>
                    <div class="message-content">
                        ${msg.title ? `<h4 class="message-title">${msg.title}</h4>` : ''}
                        <p class="message-text">${msg.message_html || ''}</p>
                        ${msg.link_url ? `
                            <a href="${msg.link_url}" class="message-link" target="_blank" rel="noopener">
                                ${msg.link_text || 'En savoir plus'}
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        carousel.innerHTML += messageHtml;
    });
    
    // Navigation
    if (allMessages.length > 1) {
        nav.classList.remove('hidden');
        initMessagesCarousel();
    } else {
        nav.classList.add('hidden');
    }
}

function initMessagesCarousel() {
    const prevBtn = document.getElementById('messages-prev');
    const nextBtn = document.getElementById('messages-next');
    const dotsContainer = document.getElementById('messages-dots');
    
    // Créer les dots
    dotsContainer.innerHTML = '';
    allMessages.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.className = `message-dot ${index === 0 ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Message ${index + 1}`);
        dot.addEventListener('click', () => goToMessage(index));
        dotsContainer.appendChild(dot);
    });
    
    // Event listeners
    prevBtn.onclick = () => navigateMessages(-1);
    nextBtn.onclick = () => navigateMessages(1);
    
    // Auto-slide toutes les 5 secondes
    startMessagesAutoSlide();
    
    // Pause on hover
    const carousel = document.getElementById('messages-carousel');
    carousel.addEventListener('mouseenter', stopMessagesAutoSlide);
    carousel.addEventListener('mouseleave', startMessagesAutoSlide);
}

function navigateMessages(direction) {
    messagesIndex += direction;
    
    if (messagesIndex < 0) messagesIndex = allMessages.length - 1;
    if (messagesIndex >= allMessages.length) messagesIndex = 0;
    
    updateMessagesSlide();
}

function goToMessage(index) {
    messagesIndex = index;
    updateMessagesSlide();
}

function updateMessagesSlide() {
    const slides = document.querySelectorAll('.message-slide');
    const dots = document.querySelectorAll('.message-dot');
    
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === messagesIndex);
    });
    
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === messagesIndex);
    });
}

function startMessagesAutoSlide() {
    stopMessagesAutoSlide();
    if (allMessages.length > 1) {
        messagesAutoSlide = setInterval(() => navigateMessages(1), 5000);
    }
}

function stopMessagesAutoSlide() {
    if (messagesAutoSlide) {
        clearInterval(messagesAutoSlide);
        messagesAutoSlide = null;
    }
}

function hideLoadingOverlay() {
    stopLoadingAnimation();
    setTimeout(() => {
        const loadingOverlay = document.getElementById('loading-overlay');
        loadingOverlay.classList.add('fade-out');
        setTimeout(() => {
            loadingOverlay.style.display = 'none';
        }, 500);
    }, 300);
}

async function init() {
    initNavbar();
    // Données rendues côté serveur : pas de cache local ni de réveil de serveur nécessaires
    const data = window.PORTFOLIO_DATA || null;
    portfolioData = data;
    updateProgress(100);
    if (data) {
        await renderAllSections(data);
    } else {
        showNotification('Impossible de charger les données', 'error', 8000);
    }
    hideLoadingOverlay();
    initPageMotion();
}

// Lancer l'initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', init);

// ----------------------------------------
// Enable clickable contact cards (robust + accessible)
// ----------------------------------------
function enableContactCardsInteractions() {
    const cards = document.querySelectorAll('.contact-card');
    if (!cards || !cards.length) return;

    cards.forEach((card, idx) => {
        // Make focusable and keyboard-usable
        card.style.cursor = 'pointer';
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');

        // Prevent clicks on internal anchors from bubbling to the card
        card.querySelectorAll('a').forEach(a => a.addEventListener('click', e => e.stopPropagation()));

        // Determine action per card position (matches current HTML ordering)
        let handler = null;
        if (idx === 0) {
            const emailEl = document.getElementById('contact-email');
            if (emailEl) handler = () => { const em = emailEl.textContent.trim(); if (em) window.location.href = `mailto:${em}`; };
        } else if (idx === 1) {
            const ln = document.getElementById('contact-linkedin');
            if (ln) handler = () => { const href = ln.href; if (href && href !== '#') window.open(href, '_blank'); };
        } else if (idx === 2) {
            const gh = document.getElementById('contact-github');
            if (gh) handler = () => { const href = gh.href; if (href && href !== '#') window.open(href, '_blank'); };
        } else {
            // Fallback: open first internal link
            const a = card.querySelector('a');
            if (a && a.href) handler = () => { window.open(a.href, '_blank'); };
        }

        if (handler) {
            card.onclick = handler;
            card.onkeydown = (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handler(); }
            };
        }
    });
}

// Run once initially and re-run when contact cards change
document.addEventListener('DOMContentLoaded', () => {
    enableContactCardsInteractions();
    const container = document.querySelector('.contact-cards');
    if (container) {
        const mo = new MutationObserver(() => enableContactCardsInteractions());
        mo.observe(container, { childList: true, subtree: true });
    }
});

// ----------------------------------------
// Scroll animations (Intersection Observer)
// ----------------------------------------
const REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let scrollObserver = null;

function initScrollAnimations() {
    if (REDUCED_MOTION || !('IntersectionObserver' in window)) return;
    // Les éléments qui entrent ensemble dans l'écran apparaissent en cascade,
    // quel que soit leur rang dans la page (pas de retard « fantôme » sur les éléments isolés).
    scrollObserver ??= new IntersectionObserver((entries) => {
        let k = 0;
        entries.filter(e => e.isIntersecting).forEach(entry => {
            const el = entry.target;
            el.style.transitionDelay = `${Math.min(k++, 5) * 70}ms`;
            el.classList.add('anim-in');
            scrollObserver.unobserve(el);
            // Une fois apparu, on retire le délai pour que les survols restent instantanés
            el.addEventListener('transitionend', () => { el.style.transitionDelay = ''; }, { once: true });
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    const observer = scrollObserver;

    // Éléments à animer au scroll
    const selectors = [
        '.section-label',
        'section h2',
        '.stat-item',
        '.timeline-item',
        '.realisation-card',
        '.project-card',
        '.competence-bloc',
        '.cert-card',
        '.pm-year',
        '.pm-lane-f .pm-card',
        '.pm-lane-x .pm-card',
        '.pm-end',
        '.why',
        '.section-description',
        '.section-divider',
        '.parcours-section > h3',
        '.stages-section > h3',
        '.certifications-block > h3',
        '.personal-projects > h3',
        '.messages-carousel',
        '.promo-badge',
        '.footer-container',
        '.contact-card',
        '#cv-section .cv-section-inner > *',
        '.profil-text > *',
        '.profil-photo-wrap',
    ];

    document.querySelectorAll(selectors.join(',')).forEach((el) => {
        if (el.classList.contains('anim-ready') || el.closest('.hidden')) return;
        el.classList.add('anim-ready');
        if (el.matches('.pm-lane-f .pm-card, .timeline-item:not(.right)')) el.classList.add('from-left');
        else if (el.matches('.pm-lane-x .pm-card, .timeline-item.right')) el.classList.add('from-right');
        observer.observe(el);
    });
    initScrollProgress();
}

/** Fine barre de progression de lecture en haut de page. */
function initScrollProgress() {
    if (document.getElementById('scroll-progress')) return;
    const bar = document.createElement('div');
    bar.id = 'scroll-progress';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);
    let ticking = false;
    const update = () => {
        ticking = false;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.transform = `scaleX(${max > 0 ? Math.min(1, window.scrollY / max) : 0})`;
    };
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    update();
}

// ========================================
// Animations de toute la page
// (tout est désactivé si le visiteur a demandé moins d'animations)
// ========================================
function initPageMotion() {
    if (REDUCED_MOTION) {
        document.body.classList.add('motion-off');
        return;
    }
    document.body.classList.add('motion-on');
    initHeroMotion();
    initCountUps();
    initCardTilt();
    initNavbarMotion();
    initScrollSpy();
}

/** Entrée du hero en cascade + parallaxe au défilement et à la souris. */
function initHeroMotion() {
    const hero = document.getElementById('hero');
    if (!hero) return;
    const parts = hero.querySelectorAll('.hero-content > *');
    parts.forEach((el, i) => { el.classList.add('hero-enter'); el.style.animationDelay = `${150 + i * 120}ms`; });

    const content = hero.querySelector('.hero-content');
    const c1 = hero.querySelector('.hero-circle-1');
    const c2 = hero.querySelector('.hero-circle-2');
    let mx = 0, my = 0, ticking = false;
    const update = () => {
        ticking = false;
        const y = Math.min(window.scrollY, hero.offsetHeight);
        const p = y / (hero.offsetHeight || 1);
        if (content) { content.style.transform = `translateY(${y * 0.25}px)`; content.style.opacity = String(1 - p * 1.1); }
        if (c1) c1.style.transform = `translate(${mx * 30}px, ${y * 0.15 + my * 30}px)`;
        if (c2) c2.style.transform = `translate(${mx * -20}px, ${y * -0.1 + my * -20}px)`;
    };
    const request = () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
    window.addEventListener('scroll', request, { passive: true });
    if (window.matchMedia('(pointer: fine)').matches) {
        hero.addEventListener('pointermove', (e) => {
            const r = hero.getBoundingClientRect();
            mx = (e.clientX - r.left) / r.width - 0.5;
            my = (e.clientY - r.top) / r.height - 0.5;
            request();
        });
    }
}

/** Les chiffres clés comptent jusqu'à leur valeur quand ils apparaissent. */
function initCountUps() {
    const nums = document.querySelectorAll('.stat-number');
    const io = new IntersectionObserver((entries) => {
        entries.filter(e => e.isIntersecting).forEach(({ target }) => {
            io.unobserve(target);
            const end = parseInt(target.textContent, 10) || 0;
            if (!end) return;
            const t0 = performance.now(), dur = 1100;
            const step = (t) => {
                const k = Math.min(1, (t - t0) / dur);
                target.textContent = String(Math.round(end * (1 - Math.pow(1 - k, 3))));
                if (k < 1) requestAnimationFrame(step);
            };
            target.textContent = '0';
            requestAnimationFrame(step);
        });
    }, { threshold: 0.6 });
    nums.forEach(n => io.observe(n));
}

/** Légère inclinaison 3D des cartes de projet au survol (souris uniquement). */
function initCardTilt() {
    if (!window.matchMedia('(pointer: fine)').matches) return;
    document.addEventListener('pointermove', (e) => {
        const card = e.target.closest?.('.realisation-card, .project-card');
        document.querySelectorAll('.is-tilting').forEach(c => { if (c !== card) { c.classList.remove('is-tilting'); c.style.removeProperty('--tilt-x'); c.style.removeProperty('--tilt-y'); } });
        if (!card) return;
        const r = card.getBoundingClientRect();
        card.classList.add('is-tilting');
        card.style.setProperty('--tilt-x', `${(((e.clientY - r.top) / r.height) - 0.5) * -6}deg`);
        card.style.setProperty('--tilt-y', `${(((e.clientX - r.left) / r.width) - 0.5) * 8}deg`);
    }, { passive: true });
}

/** La barre de navigation se cache quand on descend et revient quand on remonte. */
function initNavbarMotion() {
    const nav = document.getElementById('navbar');
    if (!nav) return;
    let last = window.scrollY;
    window.addEventListener('scroll', () => {
        const y = window.scrollY;
        const menuOpen = !document.getElementById('mobile-menu')?.classList.contains('hidden');
        nav.classList.toggle('nav-hidden', y > last && y > window.innerHeight * 0.6 && !menuOpen);
        last = y;
    }, { passive: true });
}

/** Le lien de la section affichée est souligné dans la navigation. */
function initScrollSpy() {
    const links = [...document.querySelectorAll('.nav-links .nav-link')];
    const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);
    const io = new IntersectionObserver((entries) => {
        entries.filter(e => e.isIntersecting).forEach(({ target }) => {
            links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + target.id));
        });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach(s => io.observe(s));
}

// Re-appelé après chaque render pour animer les nouveaux éléments
function refreshScrollAnimations() {
    initScrollAnimations();
}

document.addEventListener('DOMContentLoaded', initScrollAnimations);

// ----------------------------------------
// Projets personnels (section sous « Mes réalisations »)
// ----------------------------------------
function renderPersonalProjects(projects) {
    const section = document.getElementById('personal-projects');
    const container = document.getElementById('personal-projects-grid');
    if (!section || !container) return;

    if (!projects || !projects.length) {
        section.classList.add('hidden');
        return;
    }
    section.classList.remove('hidden');

    // Projets mis en avant d'abord, puis l'ordre défini dans l'admin
    // Mis en avant d'abord, puis ordre de l'admin, puis du plus récent au plus ancien
    const sorted = [...projects].sort((a, b) => (b.is_featured === true) - (a.is_featured === true) || (a.order || 0) - (b.order || 0) || (b.sort_key || '').localeCompare(a.sort_key || ''));
    const colors = ['#EC4899', '#14B8A6', '#F59E0B', '#8B5CF6', '#0EA5E9', '#84CC16'];

    container.innerHTML = sorted.map((p, i) => {
        const color = colors[i % colors.length];
        const cover = p.images && p.images.length ? getImageUrl(p.images[0]) : '';
        return `
            <a href="/projet-perso/${p.id}" class="realisation-card-link">
                <div class="realisation-card">
                    <div class="realisation-image" style="background: linear-gradient(135deg, ${color}15, ${color}05);">
                        <div class="project-decoration-1" style="background: ${color};"></div>
                        <div class="project-decoration-2" style="background: ${color};"></div>
                        ${cover
                            ? `<img src="${cover}" alt="${p.title}" class="personal-project-cover" loading="lazy" />`
                            : `<div class="project-icon" style="background: ${color}; box-shadow: 0 8px 24px ${color}40;">
                                   <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/></svg>
                               </div>`}
                        ${p.is_featured ? `<div class="realisation-company-label" style="background: ${color};">★ Mis en avant</div>` : ''}
                    </div>
                    <div class="realisation-content">
                        <span class="realisation-type" style="color: ${color}; background: ${color}12;">${p.category || 'Projet perso'}${p.date_label ? ' · ' + p.date_label : ''}</span>
                        <h4 class="realisation-title">${p.title}</h4>
                        <p class="realisation-description">${p.description_inline || ''}</p>
                        ${p.tags && p.tags.length ? `<div class="realisation-tags">${p.tags.map(t => `<span class="tag">${t.tag}</span>`).join('')}</div>` : ''}
                    </div>
                    <span class="card-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </a>`;
    }).join('');
}
