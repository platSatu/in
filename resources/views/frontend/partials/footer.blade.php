<style>
.site-footer {
    position: relative;
    background-color: #fbf1e7;
    background-image:
        linear-gradient(180deg, rgba(251,241,231,.55) 0%, rgba(251,241,231,.15) 30%, rgba(251,241,231,.15) 70%, rgba(251,241,231,.55) 100%),
        url('{{ asset('image/bg-foot.png') }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    padding: 5rem 1.5rem 3rem;
    text-align: center;
    overflow: hidden;
}

.footer-ornament {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    margin-bottom: 1.75rem;
}

.footer-ornament .ornament-line {
    width: 46px;
    height: 1px;
    background: rgba(196, 30, 30, .55);
    display: block;
}

.footer-ornament .ornament-dot {
    width: 8px;
    height: 8px;
    background: var(--footer-red, #C8102E);
    transform: rotate(45deg);
    display: block;
}

.footer-title {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-weight: 500;
    font-size: clamp(2.4rem, 6vw, 4.2rem);
    line-height: 1.15;
    color: #1F2937;
    margin: 0 0 1.5rem;
}

.footer-title .text-red {
    color: var(--footer-red, #C8102E);
}

.footer-subtitle {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: clamp(1rem, 1.6vw, 1.15rem);
    color: #4b5563;
    max-width: 34rem;
    margin: 0 auto 2.25rem;
    line-height: 1.6;
}

.footer-buttons {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 3rem;
}

.footer-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0.9rem 1.75rem;
    border-radius: 999px;
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all .25s ease;
    white-space: nowrap;
}

.footer-btn-primary {
    background: var(--footer-red, #C8102E);
    color: #fff;
    box-shadow: 0 10px 25px rgba(200, 16, 46, .25);
}

.footer-btn-primary:hover {
    background: #a30d25;
    color: #fff;
    transform: translateY(-2px);
}

.footer-btn-outline {
    background: #fff;
    color: #1F2937;
    border: 1px solid #e6dfd5;
    box-shadow: 0 6px 18px rgba(31, 41, 55, .06);
}

.footer-btn-outline:hover {
    background: #faf7f2;
    color: #1F2937;
    transform: translateY(-2px);
}

.footer-stats {
    display: flex;
    align-items: stretch;
    justify-content: center;
    gap: 0;
    flex-wrap: wrap;
    margin-bottom: 3.5rem;
}

.footer-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 2.5rem;
    border-right: 1px solid rgba(31, 41, 55, .12);
}

.footer-stat:last-child {
    border-right: none;
}

.footer-stat i {
    font-size: 1.4rem;
    color: var(--footer-red, #C8102E);
    margin-bottom: 10px;
}

.footer-stat-number {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-size: clamp(1.7rem, 3vw, 2.2rem);
    font-weight: 600;
    color: var(--footer-red, #C8102E);
    line-height: 1;
    margin-bottom: 8px;
}

.footer-stat-label {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.85rem;
    color: #374151;
    font-weight: 500;
    white-space: nowrap;
}

.footer-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 2.5rem;
}

.footer-logo-icon {
    font-size: 2.6rem;
    color: var(--footer-red, #C8102E);
    margin-bottom: 6px;
}

.footer-logo-text {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-weight: 800;
    font-size: 1.4rem;
    letter-spacing: 0.03em;
    color: var(--footer-red, #C8102E);
}

.footer-logo-cn {
    font-size: 0.8rem;
    color: var(--footer-red, #C8102E);
    opacity: .85;
    margin-top: 2px;
}

.footer-logo-tagline {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    color: var(--footer-red, #C8102E);
    text-transform: uppercase;
    margin-top: 4px;
}

.footer-bottom {
    position: relative;
    border-top: 1px solid rgba(31, 41, 55, .12);
    padding-top: 1.5rem;
    max-width: 640px;
    margin: 0 auto;
}

.footer-bottom p {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

@media (max-width: 576px) {
    .site-footer {
        padding: 3.5rem 1.25rem 2.5rem;
    }
    .footer-stats {
        gap: 0;
    }
    .footer-stat {
        padding: 0 1.1rem;
    }
    .footer-buttons {
        flex-direction: column;
        width: 100%;
        max-width: 320px;
        margin-left: auto;
        margin-right: auto;
    }
    .footer-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<footer class="site-footer" id="site-footer" style="--footer-red: #C8102E;">

    <div class="footer-ornament">
        <span class="ornament-line"></span>
        <span class="ornament-dot"></span>
        <span class="ornament-line"></span>
    </div>

    <h2 class="footer-title">Your <span class="text-red">Journey</span><br>Starts Here.</h2>

    <p class="footer-subtitle">From scholarships to student life in China,<br>our advisors are here to guide you.</p>

    <div class="footer-buttons">
        <a href="{{ route('frontend.form.wizard') }}" class="footer-btn footer-btn-primary">
            Book Free Consultation <i class="bi bi-arrow-right"></i>
        </a>
        <a href="https://wa.me/6281287625661" target="_blank" class="footer-btn footer-btn-outline">
            <i class="bi bi-chat-dots"></i> Chat with an Advisor
        </a>
    </div>

    <div class="footer-stats">
        <div class="footer-stat">
            <i class="bi bi-person"></i>
            <div class="footer-stat-number">3000+</div>
            <div class="footer-stat-label">Students Guided</div>
        </div>
        <div class="footer-stat">
            <i class="bi bi-bank"></i>
            <div class="footer-stat-number">100+</div>
            <div class="footer-stat-label">Partner Universities</div>
        </div>
        <div class="footer-stat">
            <i class="bi bi-award"></i>
            <div class="footer-stat-number">98%</div>
            <div class="footer-stat-label">Visa Success Rate</div>
        </div>
    </div>

    <div class="footer-logo">
        <i class="bi bi-mortarboard footer-logo-icon"></i>
        <img src="{{ asset('frontend/img/Logo.png') }}" width="200"/>
    </div>

    <div class="footer-bottom">
        <p><strong class="text-dark">Inastudy</strong> &mdash; &copy; {{ date('Y') }}. All rights reserved.</p>
    </div>

</footer>