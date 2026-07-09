<style>


/* ============================================
   IMPORTAR FUENTE MONTSERRAT
   ============================================ */
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap');

/* Actualizar variables de fuente */
:root {
    --color-black: #0a0a0a;
    --color-dark: #111111;
    --color-dark-gray: #1a1a1a;
    --color-gray: #2a2a2a;
    --color-light-gray: #888888;
    --color-white: #ffffff;
    --color-gold: #E5A700;
    --color-gold-dark: #c99200;
    --font-display: 'Montserrat', sans-serif;
    --font-body: 'Montserrat', sans-serif;
    --transition: all 0.3s ease;
}

/* Aplicar Montserrat globalmente */
body,
html,
* {
    font-family: 'Montserrat', sans-serif;
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
}

p, span, a, li, input, textarea, select, button {
    font-family: 'Montserrat', sans-serif;
}
/* Reset & Base */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    scroll-behavior: smooth;
    
}

body {
    font-family: var(--font-body);
    background-color: var(--color-black);
    color: var(--color-white);
    line-height: 1.6;
    overflow-x: hidden;
}

.container {
    max-width: 1320px;
    margin: 0 auto;
    padding: 0 24px;
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-display);
    font-weight: 700;
    letter-spacing: -0.01em;
}

.section-title {
    font-size: clamp(2rem, 5vw, 3rem);
    line-height: 1.1;
    margin-bottom: 1.5rem;
}

.text-gold {
    color: var(--color-gold);
}

.text-center {
    text-align: center;
}

.mt-40 {
    margin-top: 40px;
}

#colophon{
    display:none !important;
}

#site-footer{
    display:none !important;
}
/* Buttons */
.btn {
    display: inline-block;
    padding: 12px 28px;
    font-family: var(--font-body);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    border-radius: 4px;
    transition: var(--transition);
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #FFB803 0%, #F59921 100%);
    color: var(--color-black);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #F59921 0%, #FFB803 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(245, 153, 33, 0.3);
}

.btn-outline {
    background-color: transparent;
    color: var(--color-white);
    border: 1px solid var(--color-white);
}

.btn-outline:hover {
    background-color: var(--color-white);
    color: var(--color-black);
}

.btn-outline.btn-dark {
    border-color: var(--color-gray);
    color: var(--color-white);
}

.btn-outline.btn-dark:hover {
    background-color: var(--color-gray);
}

/* Navigation */
.navbar {
    position: fixed;
    top: 16px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    background: rgba(10, 10, 10, 0.95);
    backdrop-filter: blur(10px);
    padding: 14px 40px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    width: 80%;
    max-width: 1600px;
}

.nav-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
}

.nav-left, .nav-right {
    display: flex;
    align-items: center;
    gap: 32px;
}

.nav-link {
    color: var(--color-white);
    text-decoration: none;
    font-size: 13px;
    font-weight: 400;
    transition: var(--transition);
    white-space: nowrap;
}

.nav-link:hover {
    color: var(--color-gold);
}

.nav-logo {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.logo svg {
    width: 40px;
    height: 40px;
}

.logo-text {
    font-family: var(--font-display);
    font-size: 12px;
    letter-spacing: 0.15em;
    color: var(--color-gold);
}

/* Hero Section */
.hero {
    position: relative;
    min-height: 130vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: url('https://mprpickleball.com/wp-content/uploads/2026/03/a21d55c42386054de5756f7aa5283bca9c19e28e.png') center/cover no-repeat;
    padding: 120px 24px 80px;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, rgba(18, 18, 18, 0) 0%, rgba(18, 18, 18, 1) 100%);
}

.hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 980px;
}

.hero-title {
    font-size: clamp(2.5rem, 7vw, 4.5rem);
    line-height: 1.05;
    margin-bottom: 24px;
    animation: fadeInUp 0.8s ease-out;
}

.hero-subtitle {
    font-size: clamp(14px, 2vw, 16px);
    color: rgba(255, 255, 255, 0.85);
    max-width: 600px;
    margin: 0 auto 32px;
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.hero-buttons {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeInUp 0.8s ease-out 0.4s both;
}

/* Gap Section */
.gap-section {
    padding: 100px 0;
    background: #121212;
}

.gap-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.gap-image {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
}

.gap-img {
    width: 100%;
    height: auto;
    display: block;
    transition: transform 0.5s ease;
}

.gap-image:hover .gap-img {
    transform: scale(1.05);
}

.gap-content .section-title {
    color: var(--color-gold);
    font-size: clamp(2.5rem, 6vw, 3.5rem);
}

.gap-content .section-title .text-gold {
    color: var(--color-gold);
}

.gap-content p {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 16px;
    font-size: 16px;
    line-height: 1.7;
}

.gap-content .btn {
    margin-top: 16px;
}

.gap-content .btn-outline.btn-dark {
    border-color: var(--color-white);
    color: var(--color-white);
}

.gap-content .btn-outline.btn-dark:hover {
    background-color: var(--color-white);
    color: var(--color-black);
}

/* Program Section */
.program-section {
    padding: 100px 0;
    background: var(--color-black);
    position: relative;
}

.program-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--color-gray), transparent);
}

.program-header {
    max-width: 700px;
    margin: 0 auto 60px;
}

.program-subtitle {
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.feature-card {
    padding: 32px;
    background: var(--color-dark);
    border-radius: 8px;
    border: 1px solid var(--color-gray);
    transition: var(--transition);
}

.feature-card:hover {
    border-color: var(--color-gold);
    transform: translateY(-4px);
}

.feature-icon {
    width: 48px;
    height: 48px;
    margin-bottom: 20px;
}

.feature-icon svg {
    width: 100%;
    height: 100%;
}

.feature-title {
    font-size: 18px;
    margin-bottom: 12px;
    color: var(--color-gold);
}

.feature-text {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
        

    line-height: 1.7;
}

/* Levels Section */
.levels-section {
    padding: 100px 0;
    background: #303030;
}

.levels-header {
    max-width: 700px;
    margin: 0 auto 60px;
}

.levels-header .section-title {
    color: var(--color-gold);
}

.levels-subtitle {
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
}

.levels-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 24px;
    align-items: stretch;
}

.level-card {
    background: var(--color-black);
    padding: 32px 24px;
    border-radius: 8px;
    border: 1px solid var(--color-gray);
    transition: var(--transition);
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.level-card:hover {
    border-color: var(--color-gold);
}

.level-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
}

.level-icon svg {
    width: 100%;
    height: 100%;
}

.level-title {
    font-size: 16px;
    margin-bottom: 12px;
    color: var(--color-gold);
}

.level-text {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
}

/* Grid positioning */
.level-top-left {
    grid-column: 1;
    grid-row: 1;
}

.level-top-right {
    grid-column: 3;
    grid-row: 1;
}

.level-bottom-left {
    grid-column: 1;
    grid-row: 2;
}

.level-bottom-right {
    grid-column: 3;
    grid-row: 2;
}

.level-image-center {
    grid-column: 2;
    grid-row: 1 / 3;
    border-radius: 8px;
    overflow: hidden;
}

.center-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.levels-section .btn-outline.btn-dark {
    border-color: var(--color-white);
    color: var(--color-white);
}

.levels-section .btn-outline.btn-dark:hover {
    background-color: var(--color-white);
    color: var(--color-black);
}

/* Everything Section */
.everything-section {
    padding: 100px 0;
    background: var(--color-black);
}

.everything-header {
    max-width: 600px;
    margin: 0 auto 60px;
}

.everything-subtitle {
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
}

.bento-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

.bento-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.bento-column-center {
    justify-content: stretch;
}

.bento-card {
    background: var(--color-dark);
    padding: 28px;
    border-radius: 8px;
    border: 1px solid var(--color-gray);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
}

.bento-card:hover {
    border-color: var(--color-gold);
}

.bento-title {
    font-size: 24px;
    margin-bottom: 12px;
}

.bento-text {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.7;
    margin-bottom: 16px;
}

.bento-image {
    border-radius: 6px;
    overflow: hidden;
    margin-top: auto;
}

.bento-image img {
    width: 100%;
    height: auto;
    display: block;
}

.bento-link {
    color: var(--color-white);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
    margin-bottom: 16px;
}

.bento-link:hover {
    color: var(--color-gold);
}

/* Card with image at bottom */
.bento-with-image {
    flex: 1;
}

/* Card with only text */
.bento-text-only {
    flex: 0 0 auto;
}

/* Card with image at top (center column) */
.bento-image-top {
    flex: 1;
    height: 100%;
}

.bento-image-top .bento-image {
    margin-top: 0;
    margin-bottom: 20px;
}

.bento-image-top .bento-image img {
    height: 280px;
    object-fit: cover;
}

.everything-section .btn-outline.btn-dark {
    border-color: var(--color-white);
    color: var(--color-white);
}

.everything-section .btn-outline.btn-dark:hover {
    background-color: var(--color-white);
    color: var(--color-black);
}

/* Steps Section */
.steps-section {
    padding: 100px 0;
    background: #1a1a1a;
    background-image: url('https://mprpickleball.com/wp-content/uploads/2026/03/45f88a33b2736163a635ac6ce89b16b620968d6d-scaled.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

/* Overlay oscuro */
.steps-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.75); /* Ajusta la opacidad aquí (0.75 = 75% negro) */
    z-index: 1;
}

/* Asegurar que el contenido esté encima del overlay */
.steps-section .container,
.steps-section .steps-container {
    position: relative;
    z-index: 2;
}

.steps-container {
    max-width: 900px;
    margin: 0 auto;
}

.step {
    display: flex;
    gap: 60px;
    align-items: flex-start;
    margin-bottom: 80px;
    opacity: 0.3;
    transform: translateY(20px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.step.active {
    opacity: 1;
    transform: translateY(0);
}

.step-number {
    font-family: var(--font-display);
    font-size: clamp(6rem, 12vw, 10rem);
    font-weight: 700;
    line-height: 0.85;
    min-width: 180px;
    color: var(--color-gold);
    -webkit-text-stroke: 2px var(--color-gold);
    text-stroke: 2px var(--color-gold);
    transition: all 0.4s ease;
}

.step.active .step-number {
    -webkit-text-stroke: 2px var(--color-gold);
    color: var(--color-gold);
}

.step-content {
    padding-top: 24px;
    flex: 1;
}

.step-title {
    font-size: clamp(1.2rem, 2.5vw, 1.5rem);
    margin-bottom: 16px;
    color: var(--color-gold);
    font-weight: 700;
}

.step-text {
    font-size: 15px;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.7;
    max-width: 600px;
}

.steps-section .btn-outline.btn-dark {
    border-color: var(--color-white);
    color: var(--color-white);
}

.steps-section .btn-outline.btn-dark:hover {
    background-color: var(--color-white);
    color: var(--color-black);
}

/* Athletes Section */
.athletes-section {
    padding: 100px 0;
    background: var(--color-black);
}

.athletes-header {
    max-width: 700px;
    margin: 0 auto 60px;
}

.athletes-subtitle {
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
}

.athletes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.athlete-card {
    transition: var(--transition);
}

.athlete-card:hover {
    transform: translateY(-8px);
}

.athlete-image {
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}

.athlete-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
}

.athlete-card:hover .athlete-image img {
    transform: scale(1.05);
}

.athlete-title {
    font-size: 18px;
    margin-bottom: 10px;
}

.athlete-text {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
}

/* Complements Section */
.complements-section {
    padding: 100px 0;
    background: var(--color-dark);
}

.complements-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.complements-image {
    border-radius: 8px;
    overflow: hidden;
}

.complements-image img {
    width: 100%;
    height: auto;
    display: block;
}

.complements-content p {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 16px;
    font-size: 15px;
}

.complements-content .btn {
    margin-top: 20px;
}

/* Footer */
.footer {
    padding: 60px 0 40px;
    background: var(--color-black);
    border-top: 1px solid var(--color-gray);
}

.footer-content {
    text-align: center;
}

.footer-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 32px;
    margin-bottom: 24px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
}

.footer-links a:hover {
    color: var(--color-gold);
}

.footer-copy {
    color: rgba(255, 255, 255, 0.4);
    font-size: 13px;
}

/* Results Section */
.results-section {
    padding: 100px 0;
    background: var(--color-black);
}

.results-top {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    margin-bottom: 50px;
    align-items: start;
}

.results-header {
    
}

.results-label {
    display: block;
    font-size: 12px;
    letter-spacing: 0.2em;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 16px;
}

.results-intro {
    color: rgba(255, 255, 255, 0.7);
    font-size: 15px;
    line-height: 1.7;
    padding-top: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

.stats-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.stat-card {
    background: var(--color-dark);
    border-radius: 8px;
    padding: 32px;
    border: 1px solid var(--color-gray);
    transition: var(--transition);
}

.stat-card:hover {
    border-color: var(--color-gold);
}

.stat-number {
    font-family: var(--font-display);
    font-size: clamp(3rem, 8vw, 4.5rem);
    color: var(--color-white);
    display: block;
    line-height: 1;
    margin-bottom: 20px;
    font-weight: 700;
}

.stat-title {
    font-size: 14px;
    font-family: var(--font-body);
    font-weight: 600;
    color: var(--color-white);
    margin-bottom: 8px;
    letter-spacing: 0.02em;
}

.stat-text {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.6;
}

.stat-card.stat-image {
    padding: 0;
    overflow: hidden;
    border: none;
}

.stat-card.stat-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    min-height: 200px;
    display: block;
}

.stat-card.stat-full {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

/* FAQ Section */
.faq-section {
    padding: 100px 0;
    background: var(--color-dark);
}

.faq-header {
    max-width: 600px;
    margin: 0 auto 50px;
}

.faq-subtitle {
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
    font-size: 14px;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    max-width: 1000px;
    margin: 0 auto;
}

.faq-item {
    background: var(--color-black);
    border: 1px solid var(--color-gray);
    border-radius: 8px;
    overflow: hidden;
}

.faq-question {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background: transparent;
    border: none;
    color: var(--color-white);
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
    transition: var(--transition);
}

.faq-question:hover {
    color: var(--color-gold);
}

.faq-icon {
    font-size: 20px;
    color: rgba(255, 255, 255, 0.5);
    transition: var(--transition);
}

.faq-item.active .faq-icon {
    transform: rotate(45deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.faq-item.active .faq-answer {
    max-height: 200px;
    padding: 0 24px 20px;
}

.faq-answer p {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.7;
}

/* Contact Section */
.contact-section {
    padding: 100px 0;
    background: #1a1a1a;
    background-image: url('https://mprpickleball.com/wp-content/uploads/2026/03/45f88a33b2736163a635ac6ce89b16b620968d6d-scaled.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

/* Overlay oscuro */
.contact-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    z-index: 1;
}

/* Asegurar que el contenido esté encima del overlay */
.contact-section .container,
.contact-section .contact-header,
.contact-section .contact-form {
    position: relative;
    z-index: 2;
}

.contact-header {
    text-align: center;
    max-width: 600px;
    margin: 0 auto 50px;
}

.contact-label {
    display: block;
    font-size: 12px;
    letter-spacing: 0.2em;
    color: var(--color-gold);
    margin-bottom: 16px;
}

.contact-intro {
    color: var(--color-gold);
    font-size: 14px;
    margin-bottom: 16px;
}

.contact-subtext {
    color: rgba(255, 255, 255, 0.7);
    font-size: 14px;
    line-height: 1.7;
}

.contact-form {
    max-width: 700px;
    margin: 0 auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    background: var(--color-dark);
    border: 1px solid var(--color-gray);
    border-radius: 6px;
    color: var(--color-white);
    font-family: var(--font-body);
    font-size: 14px;
    transition: var(--transition);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-gold);
}

.form-group select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 24px;
}

.form-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--color-gold);
}

.form-checkbox label {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
}

.form-submit {
    text-align: center;
}

/* New Footer */
.footer-new {
    padding: 60px 0 30px;
    background: var(--color-dark);
    border-top: 1px solid var(--color-gray);
}

.footer-main {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 60px;
    margin-bottom: 40px;
}

.footer-brand {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.footer-logo-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}

.footer-brand-text {
    font-family: var(--font-display);
    font-size: 12px;
    letter-spacing: 0.15em;
    color: var(--color-gold);
}

.footer-tagline {
    font-family: var(--font-display);
    font-size: 2rem;
    color: var(--color-gold);
    margin: 8px 0;
}

.footer-buttons {
    display: flex;
    gap: 12px;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 12px;
}

.footer-contact-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.footer-contact-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-icon {
    font-size: 14px;
}

.footer-contact-item a {
    color: var(--color-gold);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
}

.footer-contact-item a:hover {
    text-decoration: underline;
}

.footer-social {
    display: flex;
    gap: 12px;
    margin-top: 16px;
}

.social-link {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-gray);
    border-radius: 50%;
    color: var(--color-white);
    transition: var(--transition);
}

.social-link:hover {
    background: var(--color-gold);
    color: var(--color-black);
}

.footer-nav {
    display: flex;
    gap: 60px;
}

.footer-nav-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.footer-nav-col a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 13px;
    transition: var(--transition);
}

.footer-nav-col a:hover {
    color: var(--color-gold);
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 24px;
    border-top: 1px solid var(--color-gray);
}

.footer-bottom p {
    color: rgba(255, 255, 255, 0.4);
    font-size: 12px;
}

.footer-credit a {
    color: var(--color-gold);
    text-decoration: none;
}

.footer-credit a:hover {
    text-decoration: underline;
}

/* Responsive for new sections */
@media (max-width: 1024px) {
    .results-intro {
        position: static;
        max-width: 100%;
        margin-bottom: 40px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .faq-grid {
        grid-template-columns: 1fr;
    }
    
    .footer-main {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .footer-nav {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .footer-bottom {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .footer-buttons {
        flex-direction: column;
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .gap-grid,
    .complements-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .gap-image {
        order: -1;
    }
    
    .features-grid,
    .athletes-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .levels-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .level-image-center {
        grid-column: span 2;
        grid-row: auto;
        max-height: 300px;
    }
    
    .bento-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .bento-warmups {
        grid-row: auto;
    }
}

@media (max-width: 768px) {
    .nav-left, .nav-right {
        display: none;
    }
    
    .navbar {
        padding: 12px 0;
    }
    
    .hero {
        min-height: 80vh;
        padding: 100px 20px 60px;
    }
    
    .hero-title {
        font-size: 2.2rem;
    }
    
    .features-grid,
    .levels-grid,
    .bento-grid,
    .athletes-grid {
        grid-template-columns: 1fr;
    }
    
    .level-image-center {
        grid-column: auto;
        max-height: 250px;
    }
    
    .step {
        flex-direction: column;
        gap: 16px;
    }
    
    .step-number {
        min-width: auto;
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .footer-links {
        flex-direction: column;
        gap: 16px;
    }
}

/* Texture Overlay for Dark Sections */
.steps-section::after,
.levels-section::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
    opacity: 0.03;
    pointer-events: none;
}

.steps-section,
.levels-section {
    position: relative;
}

/* Selection Color */
::selection {
    background: var(--color-gold);
    color: var(--color-black);
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--color-black);
}

::-webkit-scrollbar-thumb {
    background: var(--color-gray);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--color-gold);
}






/* ============================================
   RESPONSIVE STYLES - MPR PICKLEBALL
   ============================================ */

/* Tablet Landscape (1024px and below) */
@media (max-width: 1024px) {
    .container {
        padding: 0 20px;
    }
    
    /* Navbar */
    .navbar {
        width: 90%;
        padding: 12px 24px;
    }
    
    .nav-left, .nav-right {
        gap: 20px;
    }
    
    .nav-link {
        font-size: 12px;
    }
    
    /* Hero */
    .hero-content {
        max-width: 800px;
    }
    
    /* Gap Section */
    .gap-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .gap-image {
        order: -1;
    }
    
    /* Features */
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    
    /* Levels */
    .levels-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto auto;
    }
    
    .level-image-center {
        grid-column: span 2;
        grid-row: 2;
        max-height: 350px;
    }
    
    .level-top-left { grid-column: 1; grid-row: 1; }
    .level-top-right { grid-column: 2; grid-row: 1; }
    .level-bottom-left { grid-column: 1; grid-row: 3; }
    .level-bottom-right { grid-column: 2; grid-row: 3; }
    
    /* Bento Grid */
    .bento-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .bento-column:nth-child(2) {
        grid-column: span 2;
    }
    
    /* Results */
    .results-top {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .results-intro {
        padding-top: 0;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-column:first-child {
        grid-column: span 2;
    }
    
    /* Athletes */
    .athletes-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    /* Complements */
    .complements-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    /* FAQ */
    .faq-grid {
        grid-template-columns: 1fr;
    }
    
    /* Footer */
    .footer-main {
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }
    
    .footer-brand {
        grid-column: span 2;
    }
}

/* Tablet Portrait (768px and below) */
@media (max-width: 768px) {
    /* Typography */
    .section-title {
        font-size: clamp(1.75rem, 5vw, 2.5rem);
    }
    
    /* Navbar - Mobile */
    .navbar {
        width: 92%;
        padding: 10px 16px;
        top: 10px;
    }
    
    .nav-left, .nav-right {
        display: none;
    }
    
    .nav-container {
        justify-content: center;
    }
    
    .nav-logo {
        flex-direction: row;
        gap: 8px;
    }
    
    .logo svg {
        width: 32px;
        height: 32px;
    }
    
    .logo-text {
        font-size: 11px;
    }
    
    /* Hero */
    .hero {
        min-height: 85vh;
        padding: 100px 16px 60px;
    }
    
    .hero-title {
        font-size: clamp(2rem, 8vw, 3rem);
        margin-bottom: 20px;
    }
    
    .hero-subtitle {
        font-size: 14px;
        margin-bottom: 28px;
        padding: 0 10px;
    }
    
    .hero-buttons {
        flex-direction: column;
        gap: 12px;
        width: 100%;
        max-width: 280px;
        margin: 0 auto;
    }
    
    .hero-buttons .btn {
        width: 100%;
        text-align: center;
    }
    
    /* Sections Padding */
    .gap-section,
    .program-section,
    .levels-section,
    .everything-section,
    .steps-section,
    .athletes-section,
    .complements-section,
    .results-section,
    .faq-section,
    .contact-section {
        padding: 60px 0;
    }
    
    /* Gap Section */
    .gap-content .section-title {
        font-size: clamp(2rem, 7vw, 2.5rem);
    }
    
    .gap-content p {
        font-size: 15px;
    }
    
    /* Features */
    .features-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .feature-card {
        padding: 24px;
    }
    
    /* Levels */
    .levels-grid {
        grid-template-columns: 1fr;
        grid-template-rows: auto;
        gap: 16px;
    }
    
    .level-top-left,
    .level-top-right,
    .level-bottom-left,
    .level-bottom-right {
        grid-column: 1;
        grid-row: auto;
    }
    
    .level-image-center {
        grid-column: 1;
        grid-row: auto;
        max-height: 280px;
        order: -1;
    }
    
    .level-card {
        padding: 24px 20px;
    }
    
    /* Bento Grid */
    .bento-grid {
        grid-template-columns: 1fr;
    }
    
    .bento-column,
    .bento-column:nth-child(2) {
        grid-column: 1;
    }
    
    .bento-card {
        padding: 24px;
    }
    
    .bento-image-top .bento-image img {
        height: 200px;
    }
    
    /* Steps */
    .steps-container {
        max-width: 100%;
    }
    
    .step {
        flex-direction: column;
        gap: 20px;
        margin-bottom: 50px;
        text-align: center;
    }
    
    .step-number {
        font-size: clamp(4rem, 15vw, 6rem);
        min-width: auto;
        width: 100%;
    }
    
    .step-content {
        padding-top: 0;
    }
    
    .step-title {
        font-size: 1.25rem;
    }
    
    .step-text {
        max-width: 100%;
    }
    
    /* Athletes */
    .athletes-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .athlete-image img {
        height: 220px;
    }
    
    /* Results */
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-column,
    .stats-column:first-child {
        grid-column: 1;
    }
    
    .stat-card {
        padding: 24px;
    }
    
    .stat-number {
        font-size: clamp(2.5rem, 12vw, 3.5rem);
    }
    
    /* FAQ */
    .faq-question {
        padding: 16px 20px;
        font-size: 13px;
    }
    
    .faq-item.active .faq-answer {
        padding: 0 20px 16px;
    }
    
    /* Contact Form */
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 12px 14px;
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    /* Footer */
    .footer-new {
        padding: 40px 0 24px;
    }
    
    .footer-main {
        grid-template-columns: 1fr;
        gap: 32px;
        text-align: center;
    }
    
    .footer-brand {
        grid-column: 1;
        align-items: center;
    }
    
    .footer-logo-wrap {
        justify-content: center;
    }
    
    .footer-tagline {
        font-size: 1.5rem;
    }
    
    .footer-buttons {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .footer-contact-info {
        align-items: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .footer-nav {
        justify-content: center;
        gap: 40px;
    }
    
    .footer-nav-col {
        align-items: center;
    }
    
    .footer-bottom {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
}

/* Mobile Large (576px and below) */
@media (max-width: 576px) {
    .container {
        padding: 0 16px;
    }
    
    /* Navbar */
    .navbar {
        width: 94%;
        padding: 8px 12px;
        border-radius: 10px;
    }
    
    .logo svg {
        width: 28px;
        height: 28px;
    }
    
    .logo-text {
        font-size: 10px;
    }
    
    /* Hero */
    .hero {
        min-height: 80vh;
        padding: 90px 16px 50px;
    }
    
    .hero-title {
        font-size: clamp(1.75rem, 9vw, 2.5rem);
    }
    
    .hero-subtitle {
        font-size: 13px;
    }
    
    /* Buttons */
    .btn {
        padding: 10px 24px;
        font-size: 13px;
    }
    
    .btn-sm {
        padding: 8px 14px;
        font-size: 11px;
    }
    
    /* Section Titles */
    .section-title {
        font-size: clamp(1.5rem, 6vw, 2rem);
    }
    
    .gap-content .section-title {
        font-size: clamp(1.75rem, 8vw, 2.25rem);
    }
    
    /* Cards */
    .feature-card,
    .level-card,
    .bento-card,
    .stat-card {
        padding: 20px;
    }
    
    .feature-icon,
    .level-icon {
        width: 40px;
        height: 40px;
    }
    
    .feature-title,
    .level-title {
        font-size: 15px;
    }
    
    .feature-text,
    .level-text {
        font-size: 13px;
    }
    
    /* Steps */
    .step {
        margin-bottom: 40px;
    }
    
    .step-number {
        font-size: clamp(3.5rem, 18vw, 5rem);
    }
    
    .step-title {
        font-size: 1.1rem;
    }
    
    .step-text {
        font-size: 14px;
    }
    
    /* Athletes */
    .athlete-image img {
        height: 180px;
    }
    
    .athlete-title {
        font-size: 16px;
    }
    
    .athlete-text {
        font-size: 13px;
    }
    
    /* Stats */
    .stat-number {
        font-size: clamp(2.25rem, 14vw, 3rem);
        margin-bottom: 16px;
    }
    
    .stat-title {
        font-size: 13px;
    }
    
    .stat-text {
        font-size: 13px;
    }
    
    /* FAQ */
    .faq-question {
        padding: 14px 16px;
        font-size: 12px;
    }
    
    .faq-answer p {
        font-size: 12px;
    }
    
    /* Contact */
    .contact-header {
        margin-bottom: 32px;
    }
    
    .contact-intro,
    .contact-subtext {
        font-size: 13px;
    }
    
    .form-group label {
        font-size: 12px;
    }
    
    .form-checkbox label {
        font-size: 12px;
    }
    
    /* Footer */
    .footer-tagline {
        font-size: 1.25rem;
    }
    
    .footer-buttons {
        flex-direction: column;
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
    }
    
    .footer-buttons .btn {
        width: 100%;
    }
    
    .footer-nav {
        flex-direction: column;
        gap: 24px;
    }
    
    .footer-nav-col {
        gap: 10px;
    }
    
    .footer-nav-col a {
        font-size: 12px;
    }
    
    .footer-bottom p {
        font-size: 11px;
    }
    
    .social-link {
        width: 32px;
        height: 32px;
    }
    
    .social-link svg {
        width: 16px;
        height: 16px;
    }
}

/* Mobile Small (400px and below) */
@media (max-width: 400px) {
    .hero-title {
        font-size: 1.6rem;
    }
    
    .section-title {
        font-size: 1.4rem;
    }
    
    .gap-content .section-title {
        font-size: 1.6rem;
    }
    
    .step-number {
        font-size: 3rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}

/* Fix for iOS input zoom */
@media screen and (max-width: 768px) {
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    select,
    textarea {
        font-size: 16px !important;
    }
}

/* Landscape orientation fixes */
@media (max-height: 500px) and (orientation: landscape) {
    .hero {
        min-height: 100vh;
        padding-top: 80px;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        margin-bottom: 20px;
    }
}

/* High DPI screens */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .logo svg,
    .feature-icon img,
    .level-icon img {
        image-rendering: -webkit-optimize-contrast;
    }
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .step {
        opacity: 1;
        transform: none;
    }
    
    .fade-in {
        opacity: 1;
        transform: none;
    }
}




/* ============================================
   GAP SECTION - FULL WIDTH IMAGE
   ============================================ */
.gap-section {
    padding: 0;
    background: #121212;
}

.gap-section .container {
    max-width: 100%;
    padding: 0;
}

.gap-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    align-items: stretch;
}

.gap-image {
    position: relative;
    overflow: hidden;
    border-radius: 0;
    height: 100%;
}

.gap-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    min-height: 550px;
    transition: transform 0.5s ease;
}

.gap-image:hover .gap-img {
    transform: scale(1.05);
}

.gap-content {
    padding: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.gap-content .section-title {
    color: var(--color-gold);
    font-size: clamp(2.5rem, 6vw, 3.5rem);
}

.gap-content p {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 16px;
    font-size: 16px;
    line-height: 1.7;
}

.gap-content .btn {
    margin-top: 16px;
    align-self: flex-start;
}

/* ============================================
   COMPLEMENTS SECTION - FULL WIDTH IMAGE
   ============================================ */
.complements-section {
    padding: 0;
    background: var(--color-dark);
}

.complements-section .container {
    max-width: 100%;
    padding: 0;
}

.complements-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    align-items: stretch;
}

.complements-image {
    border-radius: 0;
    overflow: hidden;
    height: 100%;
}

.complements-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    min-height: 650px;
}

.complements-content {
    padding: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.complements-content p {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 16px;
    font-size: 15px;
}

.complements-content .btn {
    margin-top: 20px;
    align-self: flex-start;
}

/* ============================================
   RESPONSIVE - GAP & COMPLEMENTS
   ============================================ */
@media (max-width: 1024px) {
    .gap-grid,
    .complements-grid {
        grid-template-columns: 1fr;
    }
    
    .gap-image {
        order: -1;
    }
    
    .gap-img {
        min-height: 400px;
    }
    
    .complements-image img {
        min-height: 450px;
    }
    
    .gap-content,
    .complements-content {
        padding: 50px 40px;
    }
}

@media (max-width: 768px) {
    .gap-img {
        min-height: 300px;
    }
    
    .complements-image img {
        min-height: 350px;
    }
    
    .gap-content,
    .complements-content {
        padding: 40px 24px;
    }
    
    .gap-content .section-title {
        font-size: clamp(1.75rem, 7vw, 2.5rem);
    }
}

@media (max-width: 576px) {
    .gap-img,
    .complements-image img {
        min-height: 280px;
    }
    
    .gap-content,
    .complements-content {
        padding: 32px 20px;
    }
}




/* Fix para eliminar márgenes del body/html */
html, body {
    /*margin: 0 !important;*/
    padding: 0 !important;
    overflow-x: hidden;
    width: 100%;
}

/* Fix para contenedores de Elementor */
.elementor-section.elementor-section-full_width,
.elementor-section-wrap,
.elementor-element,
.elementor-widget-wrap,
.elementor-widget-container {
    max-width: 100% !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

/* Asegurar que las secciones full-width lleguen al borde */
.gap-section,
.complements-section {
    width: 100vw;
    margin-left: calc(-50vw + 50%);
    padding-left: 0;
    padding-right: 0;
}

/* Fix adicional para el HTML widget de Elementor */
.elementor-widget-html {
    width: 100% !important;
    max-width: 100% !important;
}

.elementor-widget-html .elementor-widget-container {
    width: 100% !important;
    max-width: 100% !important;
}


/* ============================================
   FOOTER - DISEÑO CORRECTO
   ============================================ */
.footer-wrapper {
    background: #121212;
    padding: 40px 0;
}

.footer-new {
    background: #010101;
    border-radius: 12px;
    padding: 60px;
    max-width: 1320px;
    margin: 0 auto;
}

.footer-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    margin-bottom: 40px;
}

/* Columna izquierda - Brand */
.footer-brand {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.footer-logo-wrap {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px;
}

.footer-logo-wrap img {
    height: 45px;
    width: auto;
}

.footer-brand-text {
    font-family: var(--font-display);
    font-size: 14px;
    letter-spacing: 0.15em;
    color: var(--color-white);
    font-weight: 600;
}

.footer-tagline {
    font-family: var(--font-display);
    font-size: clamp(2rem, 4vw, 2.5rem);
    color: var(--color-gold);
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

/* Contact Grid - 2x2 */
.footer-contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 40px;
    margin-bottom: 24px;
}

.footer-contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.footer-contact-item svg {
    width: 16px;
    height: 16px;
    fill: var(--color-gold);
    flex-shrink: 0;
}

.footer-contact-item a {
    color: var(--color-gold);
    text-decoration: none;
    font-size: 13px;
    transition: var(--transition);
}

.footer-contact-item a:hover {
    text-decoration: underline;
}

/* Footer Buttons */
.footer-buttons {
    display: flex;
    gap: 12px;
}

.btn-footer-primary {
    padding: 12px 28px;
    font-size: 13px;
    font-weight: 500;
    border: none;
    border-radius: 4px;
    background: var(--color-gold);
    color: var(--color-black);
    text-decoration: none;
    transition: var(--transition);
    cursor: pointer;
}

.btn-footer-primary:hover {
    background: #d4960a;
}

.btn-footer-outline {
    padding: 12px 28px;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid var(--color-white);
    border-radius: 4px;
    background: transparent;
    color: var(--color-white);
    text-decoration: none;
    transition: var(--transition);
}

.btn-footer-outline:hover {
    background: var(--color-white);
    color: var(--color-black);
}

/* Columna derecha - Nav */
.footer-right {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.footer-nav-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.footer-nav-col a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
}

.footer-nav-col a:hover {
    color: var(--color-gold);
}

/* Social Icons */
.footer-social {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.social-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-white);
    transition: var(--transition);
}

.social-icon:hover {
    color: var(--color-gold);
}

.social-icon svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

/* Footer Bottom */
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 30px;
    border-top: 1px solid var(--color-gray);
}

.footer-bottom p {
    color: rgba(255, 255, 255, 0.5);
    font-size: 13px;
    margin: 0;
}

.footer-credit a {
    color: var(--color-gold);
    text-decoration: none;
}

.footer-credit a:hover {
    text-decoration: underline;
}

/* Responsive Footer */
@media (max-width: 1024px) {
    .footer-new {
        padding: 40px 30px;
        margin: 0 20px;
    }
    
    .footer-main {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .footer-right {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .footer-wrapper {
        padding: 20px 0;
    }
    
    .footer-new {
        padding: 30px 20px;
        margin: 0 16px;
        border-radius: 8px;
    }
    
    .footer-contact-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .footer-buttons {
        flex-direction: column;
    }
    
    .footer-buttons a {
        text-align: center;
    }
    
    .footer-right {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .footer-bottom {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .footer-tagline {
        font-size: 1.5rem;
    }
}


/* ============================================
   FIX 2: LEVELS GRID - ALIGN CARDS WITH IMAGE
   ============================================ */
.levels-grid {
    display: grid !important;
    grid-template-columns: 1fr 1.2fr 1fr !important;
    grid-template-rows: 1fr 1fr !important;
    gap: 24px !important;
    align-items: stretch !important;
}

.level-image-center {
    grid-column: 2 !important;
    grid-row: 1 / 3 !important;
    border-radius: 8px !important;
    overflow: hidden !important;
    display: flex !important;
}

.level-image-center .center-img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

.level-card {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    height: 100% !important;
    min-height: 0 !important;
}





</style>


<!-- ============================================
   STICKY INDIVIDUAL SIGN UP BUTTON
   Pega esto en un widget HTML global (footer o header)
   o en cada pagina donde lo necesites
   ============================================ -->
 
<style>
/* Sticky Button */
.sticky-signup {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 9990;
    animation: stickyFadeIn 0.5s ease 1s both;
}
 
.sticky-signup-form {
    display: inline-block;
    margin: 0;
}
 
.sticky-signup-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    font-family: 'Montserrat', sans-serif !important;
    font-size: 14px;
    font-weight: 700;
    border-radius: 50px;
    cursor: pointer;
    border: none;
    background: linear-gradient(135deg, #FFB803 0%, #F59921 100%);
    color: #000000 !important;
    box-shadow: 0 4px 20px rgba(245, 153, 33, 0.4);
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
 
.sticky-signup-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(245, 153, 33, 0.5);
    background: linear-gradient(135deg, #F59921 0%, #FFB803 100%);
}
 
.sticky-signup-btn:active {
    transform: translateY(-1px);
}
 
.sticky-signup-btn svg {
    width: 18px;
    height: 18px;
    fill: #000000;
    flex-shrink: 0;
}
 
/* Hide when scrolled to top (optional - shows after scrolling) */
.sticky-signup.hidden {
    opacity: 0;
    pointer-events: none;
    transform: translateY(20px);
}
 
@keyframes stickyFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
 
/* Mobile */
@media (max-width: 768px) {
    .sticky-signup {
        bottom: 20px;
        right: 16px;
    }
    
    .sticky-signup-btn {
        padding: 12px 22px;
        font-size: 12px;
    }
    
    .sticky-signup-btn svg {
        width: 16px;
        height: 16px;
    }
}
 
@media (max-width: 400px) {
    .sticky-signup {
        bottom: 16px;
        right: 12px;
        left: 12px;
    }
    
    .sticky-signup-btn {
        width: 100%;
        justify-content: center;
    }
}






@media (max-width: 768px) {
    .levels-grid {
        display: flex !important;
        flex-direction: column !important;
        gap: 16px !important;
    }
    
    .level-top-left { order: 1 !important; }
    .level-top-right { order: 2 !important; }
    .level-image-center { order: 3 !important; max-height: 300px !important; }
    .level-bottom-left { order: 4 !important; }
    .level-bottom-right { order: 5 !important; }
    
    .level-card {
        width: 100% !important;
        height: auto !important;
    }
    
    .level-image-center {
        width: 100% !important;
        grid-column: auto !important;
        grid-row: auto !important;
    }
}














/* ============================================
   BENEFITS FOR YOUR ORGANIZATION
   ============================================ */
.benefits-org-section {
    padding: 0;
    background: #121212;
}
 
.benefits-org-section .container {
    max-width: 100%;
    padding: 0;
}
 
.benefits-org-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    align-items: stretch;
}
 
.benefits-org-image {
    position: relative;
    overflow: hidden;
    height: 100%;
}
 
.benefits-org-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    min-height: 650px;
    transition: transform 0.5s ease;
}
 
.benefits-org-image:hover img {
    transform: scale(1.05);
}
 
.benefits-org-content {
    padding: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
 
.benefits-org-content .section-title {
    color: var(--color-gold);
    font-size: clamp(2rem, 5vw, 3rem);
    margin-bottom: 2rem;
}
 
/* Checkmarks Grid - 2 columns, 4 rows */
.benefits-checks {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 32px;
}
 
.benefit-check-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
 
.benefit-check-icon {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    margin-top: 2px;
}
 
.benefit-check-icon svg {
    width: 22px;
    height: 22px;
}
 
.benefit-check-text {
    color: rgba(255, 255, 255, 0.85);
    font-size: 14px;
    line-height: 1.6;
}
 
.benefit-check-text strong {
    color: var(--color-gold);
    font-weight: 700;
    display: block;
    margin-bottom: 2px;
}
 
/* ============================================
   RESPONSIVE - BENEFITS ORG
   ============================================ */
@media (max-width: 1024px) {
    .benefits-org-grid {
        grid-template-columns: 1fr;
    }
 
    .benefits-org-image {
        order: -1;
    }
 
    .benefits-org-image img {
        min-height: 400px;
    }
 
    .benefits-org-content {
        padding: 50px 40px;
    }
}
 
@media (max-width: 768px) {
    .benefits-org-image img {
        min-height: 300px;
    }
 
    .benefits-org-content {
        padding: 40px 24px;
    }
 
    .benefits-checks {
        grid-template-columns: 1fr;
        gap: 16px;
    }
 
    .benefit-check-text {
        font-size: 13px;
    }
}
 
@media (max-width: 576px) {
    .benefits-org-image img {
        min-height: 280px;
    }
 
    .benefits-org-content {
        padding: 32px 20px;
    }
}

.faq-grid {
    grid-template-columns: 1fr !important;
    max-width: 800px !important;
}

.faq-item.active .faq-answer {
    max-height: 500px !important;
    padding: 0 24px 20px !important;
}

</style>

<div class="sticky-signup" id="stickySignup">
    <form class="sticky-signup-form" action="https://mprpickleball.com/index.php/product/pickleball-power-program/" method="post" enctype="multipart/form-data">
        <input type="hidden" name="quantity" value="1" />
        <button type="submit" name="add-to-cart" value="783" class="sticky-signup-btn">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>

            Individual Sign Up
        </button>
    </form>
</div>
 
<script>
// Optional: Show/hide based on scroll position
(function() {
    var sticky = document.getElementById('stickySignup');
    if (!sticky) return;
    
    var lastScroll = 0;
    var showAfter = 300; // Show after scrolling 300px
    
    function checkScroll() {
        var currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        
        if (currentScroll > showAfter) {
            sticky.classList.remove('hidden');
        } else {
            sticky.classList.add('hidden');
        }
        
        lastScroll = currentScroll;
    }
    
    // Initial check
    sticky.classList.add('hidden');
    checkScroll();
    
    window.addEventListener('scroll', checkScroll, { passive: true });
})();
</script>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 style="text-transform:uppercase; margin-top:225px" class="hero-title text-gold">Let Us Build Longevity<br> for your business and<br>
Pickleball Players</h1>
            <p class="hero-subtitle">The first and only fall prevention and performance program 
                designed specifically for pickleball organizations - proven to reduce injury risk while keeping players on the court longer.</p>
            <div class="hero-buttons">
                <!--
                <a href="https://mprpickleball.com/product-overview/" class="btn btn-primary">Program Overview</a>
                -->
                
            </div>
        </div>
    </section>
    
    <!-- ============================================
   HTML - Pega esto ARRIBA de <section class="gap-section">
   ============================================ -->
<section class="benefits-org-section">
    <div class="container">
        <div class="benefits-org-grid">
            <div class="benefits-org-image">
                <img src="https://mprpickleball.com/wp-content/uploads/2026/05/Paddle-Hand-Shake.jpg" alt="Benefits for your organization">
            </div>
            <div class="benefits-org-content">
                <h2 class="section-title">BENEFITS FOR YOUR<br>ORGANIZATION</h2>
                <div class="benefits-checks">
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Prioritize Safety on and Off the Court</strong>Our 3Ps approach to game prep is applicable before, during and after the game.</p>
                    </div>
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Strategic &amp; Professional</strong>Enhance performance, strengthen financial viability, optimize the bottom line.</p>
                    </div>
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Reduces Liability &amp; Insurance Costs</strong>Guards against potential legal liability issues by providing an evidence-based, safe movement and fall prevention program.</p>
                    </div>
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Gain a Competitive Edge</strong>Stand out from other facilities with a proven, connected, educational and fitness experience.</p>
                    </div>
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Meets the Needs of Every Age Group</strong>Designed to suit players of all skill levels and ages.</p>
                    </div>
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Support via In-App Messenger</strong>24-hour answers at your fingertips on any device, anywhere.</p>
                    </div>
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Build Loyalty and Trust</strong>Encourages long-term commitment between your organisation and your members/players.</p>
                    </div>
 
                    <div class="benefit-check-item">
                        <div class="benefit-check-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="#E5A700" stroke-width="2"/>
                                <path d="M7 12.5L10.5 16L17 9" stroke="#E5A700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="benefit-check-text"><strong>Longevity for your Members/Players</strong>Our evidence-based preparation program empowers players to move safer and stay longer in the game.</p>
                    </div>
 
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- The Gap Section -->
    <section class="gap-section">
        <div class="container">
            <div class="gap-grid">
                <div class="gap-image">
                    <img src="https://mprpickleball.com/wp-content/uploads/2026/01/4e6469f4744e4f9ddd95b7e4d59728e0648ddfec-768x419.png" alt="Pickleball player" class="gap-img">
                </div>
                <div class="gap-content">
                    <h2 class="section-title">THE PICKLEBALL BOOM<br>CREATED A GAP</h2>
                    <p>Pickleball is exploding. High schools are launching programs. Universities can't build courts fast enough. Facilities have waitlists.</p>
                    <p>But as participation surges, so do injuries. Falls, strains, and overuse injuries are sidelining players at every level.</p>
                    <p>The missing piece? Structured movement preparation. Most programs focus on skills and strategy but rarely address balance, hip stability, and injury prevention. MPR Pickleball fills that gap.</p>
                    <a href="#" class=" ppOrgModal btn btn-outline btn-dark">Organization Information</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Safe Movement Program Section -->
    <section class="program-section">
        <div class="container">
            <div class="program-header">
                <h2 class="section-title text-center text-gold">SAFE MOVEMENT TRAINING BUILT<br><span class="text-gold">FOR PICKLEBALL</span></h2>
                <p class="program-subtitle">MPR Pickleball Power delivers targeted warm-ups, balance and strength training, together with mobility routines—all tailored to the unique demands of pickleball movement.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                         <img src="https://mprpickleball.com/wp-content/uploads/2026/01/fi_4052096.png" />
                    </div>
                    <h3 class="feature-title text-gold">SIMPLE IMPLEMENTATION</h3>
                    <p class="feature-text">
                        No new staff, facilities, or equipment required. Players access guided video sessions on any device.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/01/Vector.png" />
                    </div>
                    <h3 class="feature-title text-gold">PROVEN MOVEMENT SCIENCE</h3>
                    <p class="feature-text">
                        Created by a Certified Medical Exercise Specialist and a Stanford Doctoral Fellow Research Scientist, each bringing decades of expertise in functional movement and injury prevention.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                          <img src="https://mprpickleball.com/wp-content/uploads/2026/01/Group.png" />
                    </div>
                    <h3 class="feature-title text-gold">SCALABLE ACROSS YOUR PROGRAM</h3>
                    <p class="feature-text">
                        Our program works whether you have 15 players or 500. Digital delivery means everyone gets the same high-quality training.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Built for Every Level Section -->
    <section class="levels-section">
        <div class="container">
            <div class="levels-header">
                <h2 class="section-title text-center text-gold">BUILT FOR EVERY LEVEL OF<br><span class="text-gold">PICKLEBALL</span></h2>
                <p class="levels-subtitle">MPR Pickleball is a digital training platform that delivers targeted warm-ups, balance work, strength training, and mobility routines — all tailored to the unique demands of pickleball.</p>
            </div>
            <div class="levels-grid">
                <div class="level-card level-top-left">
                    <div class="level-icon">
                       <img src="https://mprpickleball.com/wp-content/uploads/2026/01/fi_1026971.png" />
                    </div>
                    <h3 class="level-title">HIGH SCHOOLS</h3>
                    <p class="level-text">Give student-athletes a competitive edge with sport-specific movement training that reduces injury risk and builds long-term athleticism.</p>
                </div>
                <div class="level-image-center">
                    <img src="https://mprpickleball.com/wp-content/uploads/2026/01/deff474e6ee3e449e87afaaee07b8e3218a34245-768x865.png" alt="Pickleball players" class="center-img">
                </div>
                <div class="level-card level-top-right">
                    <div class="level-icon">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/01/fi_5404967.png" />
                    </div>
                    <h3 class="level-title">UNIVERSITIES & CLUBS</h3>
                    <p class="level-text">Provide club sport members with professional-grade training that supports performance and keeps participation rates high.</p>
                </div>
                <div class="level-card level-bottom-left">
                    <div class="level-icon">
                         <img src="https://mprpickleball.com/wp-content/uploads/2026/01/fi_17155564.png" />
                    </div>
                    <h3 class="level-title">PICKLEBALL FACILITIES & CLUBS</h3>
                    <p class="level-text">Add value for members with an exclusive wellness benefit that keeps them playing longer and more confidently.</p>
                </div>
                <div class="level-card level-bottom-right">
                    <div class="level-icon">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/01/x30_1.png" />
                    </div>
                    <h3 class="level-title">PARKS & RECREATION / MUNICIPAL PROGRAMS</h3>
                    <p class="level-text">Offer community players a safe, accessible way to stay active — reducing liability concerns and increasing program retention.</p>
                </div>
            </div>
            <div class="text-center mt-40">
                <!--
                <a href="#" class="ppOrgModal btn btn-outline btn-dark">Get in Touch</a>-->
            </div>
        </div>
    </section>

    <!-- Everything You Need Section -->
    <section class="everything-section">
        <div class="container">
            <div class="everything-header">
                <h2 class="section-title text-center text-gold">EVERYTHING YOUR ORGANIZATION<BR> NEEDS TO LAUNCH</h2>
                <p class="everything-subtitle">MPR Pickleball provides a complete movement training system - no assembly required.</p>
            </div>
            <div class="bento-grid">
                <!-- Column 1 -->
                <div class="bento-column">
                    <div class="bento-card bento-with-image">
                        <h3 class="bento-title text-gold">MOVEMENT PREP AND WARM-UPS</h3>
                        <p class="bento-text">Sport-specific sequences that activate key muscles and prepare the body for quick direction changes and explosive movements.</p>
                        <div class="bento-image">
                            <img src="https://mprpickleball.com/wp-content/uploads/2026/03/d7e5472973279800db84ae81f3c1c3c5d4e4d13f-768x512.png" alt="Warm-ups">
                        </div>
                    </div>
                    <div class="bento-card bento-text-only">
                        <h3 class="bento-title text-gold">BALANCE AND FALL PREVENTION</h3>
                        <p class="bento-text">Evidence-based exercises that improve stability, proprioception, and confidence on the court.</p>
                    </div>
                </div>
                
                <!-- Column 2 (Center) -->
                <div class="bento-column bento-column-center">
                    <div class="bento-card bento-image-top">
                        <div class="bento-image">
                            <img src="https://mprpickleball.com/wp-content/uploads/2026/03/970fb81f12e0ed4522c2e188c12b7e67f7592f51-768x512.png" alt="Strength training">
                        </div>
                        <h3 class="bento-title text-gold">STRENGTH & MOBILITY FOR PICKLEBALL</h3>
                        <p class="bento-text">
                            We use resistance bands to develop targeted routines for hip strength, rotational power, 
                            shoulder stability, and ankle mobility—the foundations of safe, effective play.
                        </p>
                    </div>
                </div>
                
                <!-- Column 3 -->
                <div class="bento-column">
                    <div class="bento-card bento-with-image">
                        <h3 class="bento-title text-gold">DIGITAL DELIVERY</h3>
                        <p class="bento-text">Access on any device. No apps to download. Players train on their schedule, guided by clear video instruction.</p>
                        <a href="#" class="bento-link">Launch →</a>
                        <div class="bento-image">
                            <img src="https://mprpickleball.com/wp-content/uploads/2026/04/71dee5e42dfab204e4ec333625d07157064cc1f4.png" alt="Digital delivery">
                        </div>
                    </div>
                    <div class="bento-card bento-text-only">
                        <h3 class="bento-title text-gold">IMPLEMENTATION SUPPORT</h3>
                        <p class="bento-text">We help you launch smoothly with onboarding materials, communication templates, and technical support.</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-40">
                <a href="#" class="ppOrgModal btn btn-primary">Get Started</a>
            </div>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="steps-section">
        <div class="container">
            <div class="steps-container">
                <div class="step" data-step="1">
                    <div class="step-number">01</div>
                    <div class="step-content">
                        <h3 class="step-title text-gold">PARTNERSHIP SETUP</h3>
                        <p class="step-text">We discuss your goals, player demographics, and program structure. Then we customize access and materials for your organization.</p>
                    </div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-number">02</div>
                    <div class="step-content">
                        <h3 class="step-title">PLAYER ONBOARDING</h3>
                        <p class="step-text">Share access links with your team. Athletes log in on any device and begin guided sessions. No downloads, no friction, no delays. Training starts within 24 hours of enrollment.</p>
                    </div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-number">03</div>
                    <div class="step-content">
                        <h3 class="step-title">GROW WITHOUT ADDING STAFF BURDEN</h3>
                        <p class="step-text">Your players train independently. We provide usage reports and ongoing support to ensure smooth adoption.</p>
                        
                        <a style="margin-top:50px" href="#" class="btn btn-primary ppOrgModal">Get Started</a>
                    </div>
                </div>
                <div class="text-l mt-40">
                    <!--
                    <a href="#" class="btn btn-primary">Get Started</a>-->
                </div>
            </div>
        </div>
    </section>

    <!-- What Athletes Gain Section -->
    
    <!-- What Athletes Gain Section -->
    <section class="athletes-section">
        <div class="container">
            <div class="athletes-header">
                <h2 class="section-title text-center text-gold">WHAT PLAYERS GAIN</h2>
                <p class="athletes-subtitle">When players train consistently with MPR Pickleball Power, 
                    they experience measurable improvements in safety, confidence, and performance.</p>
            </div>
            <div class="athletes-grid">
                <div class="athlete-card">
                    <div class="athlete-image">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/03/2d0497a283d45d65747c8b32944923cd777d4bcd-768x807.png" alt="Resistance bands">
                    </div>
                    <h3 class="athlete-title ">RESISTANCE BANDS</h3>
                    <p class="athlete-text">
                        A compact, lightweight home gym delivered to your door; complete with travel-ready, easy-store, multi-resistance bands.
                    </p>
                </div>
                <div class="athlete-card">
                    <div class="athlete-image">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/03/a96abdc225d57b56a7c58ec8f694e444a62d4b01-1-768x512.jpg" alt="Reduced injury risk">
                    </div>
                    <h3 class="athlete-title ">REDUCED INJURY RISK</h3>
                    <p class="athlete-text">Increased strength, improved balance, hip stability, and movement 
                        control mean fewer falls and overuse injuries.</p>
                </div>
                <div class="athlete-card">
                    <div class="athlete-image">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/03/18008c34a5f74fd05f8f03ed2fafc937ad220453-1-768x512.jpg" alt="Long-term participation">
                    </div>
                    <h3 class="athlete-title ">GREATER CONFIDENCE & MOVEMENT QUALITY</h3>
                    <p class="athlete-text">Players move with better form, recover faster between points, and feel more stable on the court.</p>
                </div>
            </div>
        </div>
    </section>

     <!-- Complements Section -->
    <section class="complements-section">
        <div class="container">
            <div class="complements-grid">
                <div class="complements-image">
                    <img src="https://mprpickleball.com/wp-content/uploads/2026/01/06b61841fddce5f2dff4a9a5322eedd0db341fc7-scaled.jpg" alt="Training">
                </div>
                <div class="complements-content">
                    <h2 class="section-title text-gold">COMPLEMENTS YOUR<br><span class="text-gold">CURRENT PROGRAM</span></h2>
                    <p>
                        MPR Pickleball doesn't replace your coaches, trainers, or existing programming. It enhances them.<br>

Think of it as the "strength and conditioning" layer that most pickleball programs don't have—delivered digitally so your staff can focus on skill development, strategy, and competition.
<br>
Coaches love it because:<br>
                    </p>
                    <p>*Players come to practice better prepared</p><br>
                    <p>*Injury rates drop</p><br>
                    <p>*Players perform more consistently</p><br>
                    <p>*It requires zero extra coaching time</p><br>
                    <a href="#" class="btn btn-primary ppOrgModal">Get Started</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Results Section -->
   <!-- Results Section -->
    <!--
    <section class="results-section">
        <div class="container">
            <div class="results-top">
                <div class="results-header">
                    <span class="results-label">RESULTS</span>
                    <h2 class="section-title">HOW ORGANIZATIONS<br>ARE SEEING REAL<br>CHANGE</h2>
                </div>
                <p class="results-intro">MPR Pickleball is currently piloting with high schools, universities, and facilities across the country. Here's what early players are experiencing:</p>
            </div>
            
            <div class="stats-grid">
               
                <div class="stats-column">
                    <div class="stat-card stat-full">
                        <span class="stat-number">87%</span>
                        <h4 class="stat-title">ATHLETES REPORT IMPROVED STABILITY</h4>
                        <p class="stat-text">Players notice better balance within the first month</p>
                    </div>
                </div>
                
                
                <div class="stats-column">
                    <div class="stat-card stat-image">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/01/4904336e9281108505e9303aca8e335395e2373e.jpg" alt="Athletes training">
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">62%</span>
                        <h4 class="stat-title">FEWER PRACTICE INTERRUPTIONS</h4>
                        <p class="stat-text">Organizations see reduced downtime from minor injuries</p>
                    </div>
                </div>
                
                
                <div class="stats-column">
                    <div class="stat-card">
                        <span class="stat-number">94%</span>
                        <h4 class="stat-title">CONTINUED PROGRAM ADOPTION</h4>
                        <p class="stat-text">Teams renew because athletes stay engaged and healthy</p>
                    </div>
                    <div class="stat-card stat-image">
                        <img src="https://mprpickleball.com/wp-content/uploads/2026/01/1d19301896567aace79db34daea3343e4af29fbd.jpg" alt="Team collaboration">
                    </div>
                </div>
            </div>
        </div>
    </section>
    -->


    <!-- FAQ Section -->
    <section id="faq-section" class="faq-section">
        <div class="container">
            <div class="faq-header">
                <h2 class="section-title text-center">FAQ</h2>
                <p class="faq-subtitle">Common questions about implementation, cost, and how MPR Pickleball fits into your program.</p>
            </div>
            
            <div class="faq-grid">


            <div class="faq-item ">
    <button class="faq-question">
        <span>How to Purchase?</span>
        <span class="faq-icon">×</span>
    </button>
    <div class="faq-answer">
        <p style="margin-bottom: 8px;"><strong style="color: #E5A700;">1.</strong> Go to mprpickleball.com (NOT the app store)</p>
        <p style="margin-bottom: 8px;"><strong style="color: #E5A700;">2.</strong> Click "Individual Sign Up"</p>
        <p style="margin-bottom: 8px;"><strong style="color: #E5A700;">3.</strong> Complete purchase info for payment and band delivery</p>
        <p style="margin-bottom: 4px;"><strong style="color: #E5A700;">4.</strong> To Download MPR Med Exercise app (First email)</p>
        <p style="margin-bottom: 4px; padding-left: 20px;"><strong style="color: #E5A700;">a.</strong> Check personal email from MPR Med Exercise</p>
        <p style="margin-bottom: 4px; padding-left: 20px;"><strong style="color: #E5A700;">b.</strong> Copy Login and Password as identified in email</p>
        <p style="margin-bottom: 4px; padding-left: 20px;"><strong style="color: #E5A700;">c.</strong> Go to App Store – Download MPR Med Exercise</p>
        <p style="margin-bottom: 4px; padding-left: 20px;"><strong style="color: #E5A700;">d.</strong> Paste Login and Password from email into app sign in</p>
        <p style="margin-bottom: 8px; padding-left: 20px;"><strong style="color: #E5A700;">e.</strong> Access is completed (begin program)</p>
        <p><strong style="color: #E5A700;">5.</strong> Check personal email for tracking (second email)</p>
    </div>
</div>


                <div class="faq-item">
                    <button class="faq-question">
                        <span>How much does it cost?</span>
                        <span class="faq-icon">×</span>
                    </button>
                    <div class="faq-answer">
                        <p>Pricing is based on the size of your program and length of partnership. Contact us for a custom quote — most organizations find it more affordable than hiring a part-time trainer.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Is this for all ages?</span>
                        <span class="faq-icon">×</span>
                    </button>
                    <div class="faq-answer">
                        <p>Yes. The program works for athletes across the lifespan.<br></p>
                        <ul>
                            <li class="text-gold">Teens</li>
                             <li class="text-gold">Adults</li>
                              <li class="text-gold">Older Adults</li>
                        </ul>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Do we need special equipment?</span>
                        <span class="faq-icon">×</span>
                    </button>
                    <div class="faq-answer">
                        <p>No. Most exercises use resistance bands (provided) or bodyweight only. Everything is designed for minimal equipment and space.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question">
                        <span>How do players engage with the program?</span>
                        <span class="faq-icon">×</span>
                    </button>
                    <div class="faq-answer">
                        <p>We provide onboarding materials, progress tracking, and communication templates to drive adoption.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question">
                        <span>How much time does it require?</span>
                        <span class="faq-icon">×</span>
                    </button>
                    <div class="faq-answer">
                        <p>Players typically spend 15–25 minutes per session, 2–3 times per week. Sessions are flexible and can be done at home or on-site.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Can we try it before committing long-term?</span>
                        <span class="faq-icon">×</span>
                    </button>
                    <div class="faq-answer">
                        <p>Yes. We offer small group pilot programs for organizations that want to test the platform.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Will this take time away from skill training?</span>
                        <span class="faq-icon">×</span>
                    </button>
                    <div class="faq-answer">
                        <p>No. MPR Pickleball is designed to complement — not compete with — court time. Many programs encourage players to complete sessions on off-days or before practice.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="container">
            <div class="contact-header">
                <span class="contact-labels">CONTACT US</span>
                <h2 class="section-title text-center text-gold">READY TO BUILD SAFER PLAYERS?</h2>
                <p class="contact-sub">Pilot partnerships for Spring 2026 are now open. Limited to 10 organizations. Apply by February 15 to secure your spot.</p>
                <p class="contact-subtext">Join the high schools, universities, and facilities already using MPR Pickleball to reduce injuries, improve performance, and create a culture of long-term athlete wellness.</p>
            </div>
            
            <form class="contact-form" id="contactForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="organization">Your Organization</label>
                        <input type="text" id="organization" name="organization" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="source">How did you hear about us?</label>
                    <select id="source" name="source">
                        <option value="">Select one...</option>
                        <option value="search">Search Engine</option>
                        <option value="social">Social Media</option>
                        <option value="referral">Referral</option>
                        <option value="conference">Conference/Event</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Tell us about your program..."></textarea>
                </div>
                
                <div class="form-checkbox">
                    <input type="checkbox" id="privacy" name="privacy" required>
                    <label for="privacy">I agree to the privacy policy</label>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </section>

<!-- Footer -->
<div class="footer-wrapper">
    <footer class="footer-new">
        <div class="footer-main">
            <!-- Columna Izquierda - Brand (1/2) -->
            <div class="footer-brand">
                <div class="footer-logo-wrap">
    <img src="https://mprpickleball.com/wp-content/uploads/2026/01/a5f32a914ed3294265e5d4079880a610e4de8b7f.png" alt="MPR Pickleball" style="height: 60px; width: auto;">
    
</div>
                
                <h3 class="footer-tagline">PARTNER WITH US</h3>
                
                <div class="footer-contact-grid">
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <a href="mailto:mike@mprpickleball.com">mike@mprpickleball.com</a>
                    </div>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                        <a href="tel:+16024325216">+1 (602) 980-4199</a>
                    </div>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <a href="mailto:wayne@mprpickleball.com">wayne@mprpickleball.com</a>
                    </div>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                        <a href="tel:+14809802739">+1 (480) 980-2739</a>
                    </div>
                </div>
                
                <div class="footer-buttons">
                    <a href="#contact" class="btn-footer-primary">Contact</a>
                    <a href="https://mprmedexercise.com" class="btn-footer-outline">MPR Med Exercise</a>
                </div>
            </div>
            
            <!-- Columna Derecha - Navigation (1/2 dividido en 2) -->
            <div class="footer-right">
                <!-- Nav Column 1 -->
                <div class="footer-nav-col">
                    <a href="#">How It Works</a>
                    <a href="#">Who We Serve</a>
                    <a href="#">How it works</a>
                    <a href="#">MPR Med Exercise</a>
                </div>
                
                <!-- Nav Column 2 -->
                <div class="footer-nav-col">
                    <a href="#">Contact</a>
                    <a href="#">FAQ</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    
                    <div class="footer-social">
                        <a href="#" class="social-icon">
                            <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="#" class="social-icon">
                            <svg viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2025 MPR Pickleball. All rights reserved.</p>
            <p class="footer-credit">Website designed by <a href="#">Freeplay Studios</a> | <a href="https://app.mprpickleball.com/privacy-policy.html">Privacy Policy</a></p>
        </div>
    </footer>
</div>

    <script src="script.js"></script>
</body>
</html>
    
    
    <script>
        // MPR Pickleball - JavaScript

document.addEventListener('DOMContentLoaded', () => {
    // Initialize all modules
    initScrollAnimations();
    initNavbarScroll();
    initSmoothScroll();
    initParallax();
    initFAQ();
    initContactForm();
    initCounterAnimation();
    initStepsAnimation();
});

// Scroll-triggered animations
function initScrollAnimations() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Stagger children if they have the stagger class
                const staggerChildren = entry.target.querySelectorAll('.stagger-child');
                staggerChildren.forEach((child, index) => {
                    child.style.transitionDelay = `${index * 0.1}s`;
                    child.classList.add('visible');
                });
            }
        });
    }, observerOptions);

    // Add fade-in class to elements that should animate
    const animatedElements = document.querySelectorAll(
        '.section-title, .feature-card, .level-card, .bento-card, .step, .athlete-card, .gap-content, .complements-content'
    );

    animatedElements.forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
    });

    // Add stagger to grid items
    const grids = document.querySelectorAll('.features-grid, .athletes-grid, .bento-grid');
    grids.forEach(grid => {
        const children = grid.children;
        Array.from(children).forEach((child, index) => {
            child.style.transitionDelay = `${index * 0.1}s`;
        });
    });
}

// Navbar background on scroll
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        // Add/remove scrolled class
        if (currentScroll > 50) {
            navbar.style.background = 'rgba(10, 10, 10, 0.98)';
            navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.4)';
        } else {
            navbar.style.background = 'rgba(10, 10, 10, 0.95)';
            navbar.style.boxShadow = 'none';
        }

        // Hide/show navbar on scroll direction
        if (currentScroll > lastScroll && currentScroll > 100) {
            navbar.style.top = '-100px';
        } else {
            navbar.style.top = '16px';
        }

        lastScroll = currentScroll;
    });
}

// Smooth scroll for anchor links
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Parallax effect for hero
function initParallax() {
    const hero = document.querySelector('.hero');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * 0.3;
        
        if (hero) {
            hero.style.backgroundPositionY = `calc(50% + ${rate}px)`;
        }
    });
}

// Button hover effect with ripple
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('mouseenter', function(e) {
        const x = e.clientX - this.getBoundingClientRect().left;
        const y = e.clientY - this.getBoundingClientRect().top;
        
        const ripple = document.createElement('span');
        ripple.className = 'btn-ripple';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        
        this.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    });
});

// Counter animation for stats (if needed)
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    const timer = setInterval(() => {
        start += increment;
        element.textContent = Math.floor(start);
        
        if (start >= target) {
            element.textContent = target;
            clearInterval(timer);
        }
    }, 16);
}

// Typing effect for hero title (optional)
function typeWriter(element, text, speed = 50) {
    let i = 0;
    element.textContent = '';
    
    function type() {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// Mobile menu toggle (for future implementation)
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
}

// Image lazy loading
function initLazyLoad() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Add CSS for button ripple effect and notifications dynamically
const style = document.createElement('style');
style.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .btn-ripple {
        position: absolute;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
        width: 100px;
        height: 100px;
        margin-left: -50px;
        margin-top: -50px;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .navbar {
        transition: transform 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
    }
    
    .notification {
        position: fixed;
        bottom: 24px;
        right: 24px;
        padding: 16px 24px;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 16px;
        z-index: 9999;
        transform: translateY(100px);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    
    .notification.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    .notification-success {
        border-color: #E5A700;
    }
    
    .notification-error {
        border-color: #ff4444;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: #888;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    
    .notification-close:hover {
        color: #fff;
    }
`;
document.head.appendChild(style);

// Console welcome message
console.log('%cMPR Pickleball', 'color: #E5A700; font-size: 24px; font-weight: bold;');
console.log('%cSafe Movement Training Built for Pickleball', 'color: #888; font-size: 12px;');

// FAQ Accordion
function initFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Close other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });
}

// Contact Form Handling
/*
function initContactForm() {
    const form = document.getElementById('contactForm');
    
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Validate
            if (!data.name || !data.email || !data.organization) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }
            
            if (!data.privacy) {
                showNotification('Please agree to the privacy policy.', 'error');
                return;
            }
            
            // Simulate form submission
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                showNotification('Thank you! We\'ll be in touch soon.', 'success');
                form.reset();
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });
    }
}
*/
function initContactForm() {
    const form = document.getElementById('contactForm');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(form);
        
        // Validate
        if (!formData.get('name') || !formData.get('email') || !formData.get('organization')) {
            showNotification('Please fill in all required fields.', 'error');
            return;
        }
        
        if (!formData.get('privacy')) {
            showNotification('Please agree to the privacy policy.', 'error');
            return;
        }
        
        // Add action for WordPress AJAX
        formData.append('action', 'mpr_contact_submit');
        
        var submitBtn = form.querySelector('button[type="submit"]');
        var originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;
        
        // Get ajax URL - try mprAjax first, fallback to hardcoded
        var ajaxUrl = (typeof mprAjax !== 'undefined') 
            ? mprAjax.url 
            : '/wp-admin/admin-ajax.php';
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showNotification('Thank you! We\'ll be in touch soon.', 'success');
                form.reset();
            } else {
                showNotification(data.data || 'Something went wrong. Please try again.', 'error');
            }
        })
        .catch(function() {
            showNotification('Network error. Please try again.', 'error');
        })
        .finally(function() {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });
}
// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto close
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Counter Animation for Stats
function initCounterAnimation() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const text = element.textContent;
                const target = parseInt(text);
                
                if (!isNaN(target) && !element.dataset.animated) {
                    element.dataset.animated = 'true';
                    animateNumber(element, target);
                }
                
                counterObserver.unobserve(element);
            }
        });
    }, { threshold: 0.5 });
    
    statNumbers.forEach(stat => counterObserver.observe(stat));
}

// Steps Scroll Animation
function initStepsAnimation() {
    const steps = document.querySelectorAll('.step');
    
    const stepsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            } else {
                // Remove active class when out of view for re-animation on scroll up
                entry.target.classList.remove('active');
            }
        });
    }, { 
        threshold: 0.4,
        rootMargin: '-10% 0px -10% 0px'
    });
    
    steps.forEach(step => {
        stepsObserver.observe(step);
    });
    
    // Also trigger on scroll for more dynamic effect
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                updateStepsOnScroll(steps);
                ticking = false;
            });
            ticking = true;
        }
    });
}

function updateStepsOnScroll(steps) {
    const windowHeight = window.innerHeight;
    const triggerPoint = windowHeight * 0.6;
    
    steps.forEach(step => {
        const rect = step.getBoundingClientRect();
        const stepCenter = rect.top + (rect.height / 2);
        
        if (stepCenter < triggerPoint && stepCenter > windowHeight * 0.2) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

function animateNumber(element, target) {
    const duration = 2000;
    const start = 0;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + (target - start) * easeOut);
        
        element.textContent = current + '%';
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = target + '%';
        }
    }
    
    requestAnimationFrame(update);
}
    </script>