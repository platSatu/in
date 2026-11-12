<style>
.section-6 {
    position: relative;
    background-color: #f1ebe1;
    background-image: url('{{ asset('image/bg2.png') }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    padding: 5rem 1.5rem 6rem;
}

.section-6__inner {
    /* === FIX === dinaikin dari 780px -> 1100px. Sebelumnya video-wrap &
       cards punya max-width 900px sendiri, tapi ke-block sama parent
       .section-6__inner yang cuma 780px, jadi videonya ga pernah bisa
       lebih lebar dari itu. */
    max-width: 1100px;
    margin: 0 auto;
    text-align: center;
}

.section-6__eyebrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--s6-red, #C8102E);
    margin-bottom: 1.25rem;
}

.section-6__eyebrow .line {
    width: 26px;
    height: 1px;
    background: rgba(200, 16, 46, .55);
    display: block;
}

.section-6__title {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-weight: 500;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    line-height: 1.25;
    color: #1F2937;
    margin: 0 0 1.1rem;
}

.section-6__title .text-red {
    color: var(--s6-red, #C8102E);
}

.section-6__desc {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.9rem;
    line-height: 1.7;
    color: #6b7280;
    max-width: 34rem;
    margin: 0 auto 3rem;
}

/* ===== Hero video ===== */

.section-6__video-wrap {
    /* === FIX === video diperbesar, dari 900px -> 1100px (ngikutin full lebar .section-6__inner) */
    max-width: 1100px;
    margin: 0 auto 2.5rem;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(31, 41, 55, .18);
    background: #e5ddcf;
}

.section-6__video {
    display: block;
    width: 100%;
    aspect-ratio: 16 / 9;
    object-fit: cover;
}

/* ===== Camp cards ===== */

.section-6__cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.75rem;
    max-width: 900px;
    margin: 0 auto;
    text-align: left;
}

.section-6__card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(31, 41, 55, .08);
    transition: transform .25s ease, box-shadow .25s ease;
    display: flex;
    flex-direction: column;
}

.section-6__card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 45px rgba(31, 41, 55, .14);
}

.section-6__card-media {
    aspect-ratio: 16 / 10;
    background: #e5ddcf;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #9a9186;
}

/* Ganti .section-6__card-media dengan <img src="..." class="section-6__card-media"
   (object-fit: cover) begitu poster Summer Camp / Winter Camp sudah ada. */

.section-6__card-media i {
    font-size: 1.4rem;
}

.section-6__card-media span {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.72rem;
}

.section-6__card-body {
    padding: 1.5rem 1.5rem 1.75rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.section-6__card-body h3 {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-weight: 600;
    font-size: 1.3rem;
    color: #1F2937;
    margin: 0 0 0.6rem;
}

.section-6__card-body p {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.83rem;
    line-height: 1.6;
    color: #6b7280;
    margin: 0 0 1rem;
}

.section-6__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 1.25rem;
}

.section-6__tag {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.72rem;
    font-weight: 500;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    padding: 5px 12px;
    border-radius: 999px;
    white-space: nowrap;
}

.section-6__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    align-self: flex-start;
    margin-top: auto;
    background: var(--s6-red, #C8102E);
    color: #fff;
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-weight: 600;
    font-size: 0.82rem;
    padding: 0.6rem 1.15rem;
    border-radius: 999px;
    text-decoration: none;
    transition: all .2s ease;
}

.section-6__btn:hover {
    background: #a30d25;
    color: #fff;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .section-6__cards {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .section-6 {
        padding: 3.5rem 1.25rem 3.5rem;
    }
}
</style>

<section class="section-6" id="section-6" style="--s6-red: #C8102E;">
    <div class="section-6__inner">

        <div class="section-6__eyebrow">
            <span class="line"></span> INA TRIP <span class="line"></span>
        </div>

        <h2 class="section-6__title">Experience <span class="text-red">China.</span><br>Beyond the classroom.</h2>

        <p class="section-6__desc">
            Discover China through unforgettable educational travel experiences.
            Visit top universities, explore local culture, connect with students,
            and create lifelong memories through our signature camp programs.
        </p>

        <div class="section-6__video-wrap">
            <video class="section-6__video" autoplay muted loop playsinline>
                <source src="{{ asset('image/video_ina.mp4') }}" type="video/mp4">
            </video>
        </div>

        <div class="section-6__cards">

            <div class="section-6__card">
                <div class="section-6__card-media">
                    <i class="bi bi-image"></i>
                    <span>Summer Camp poster</span>
                </div>
                <div class="section-6__card-body">
                    <h3>Summer Camp</h3>
                    <p>Experience China's top universities, iconic destinations, and vibrant city life through an unforgettable educational journey.</p>
                    <div class="section-6__tags">
                        <span class="section-6__tag">University Visits</span>
                        <span class="section-6__tag">Company Visits</span>
                        <span class="section-6__tag">Cultural Experiences</span>
                    </div>
                    <a href="#" class="section-6__btn">Explore Summer Camp <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="section-6__card">
                <div class="section-6__card-media">
                    <i class="bi bi-image"></i>
                    <span>Winter Immersion Camp poster</span>
                </div>
                <div class="section-6__card-body">
                    <h3>Winter Immersion Camp</h3>
                    <p>Experience academic immersion, Chinese culture, and unforgettable winter adventures designed for future global students.</p>
                    <div class="section-6__tags">
                        <span class="section-6__tag">Campus Experience</span>
                        <span class="section-6__tag">Cultural Activities</span>
                        <span class="section-6__tag">Winter Attractions</span>
                    </div>
                    <a href="#" class="section-6__btn">Explore Winter Camp <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

        </div>

    </div>
</section>
