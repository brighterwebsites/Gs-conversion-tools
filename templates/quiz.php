<?php
/**
 * Quiz template — rendered inline, no iframe.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gs-quiz">

    <div class="gs-quiz__header">
        <h1>Find Your Perfect Stable</h1>
        <p>Answer 6 quick questions for a personalised recommendation</p>
    </div>

    <div class="gs-quiz__progress">
        <div class="gs-quiz__progress-label">
            <span>Question <span id="gs-q-num">1</span> of 6</span>
            <span id="gs-q-pct">0%</span>
        </div>
        <div class="gs-quiz__progress-bar">
            <div class="gs-quiz__progress-fill" id="gs-q-fill"></div>
        </div>
    </div>

    <div class="gs-quiz__questions" id="gs-questions">

        <!-- Q1 -->
        <div class="gs-quiz__question" data-question="1">
            <h2 class="gs-quiz__q-title">What's your starting point?</h2>
            <p class="gs-quiz__q-sub">This helps us understand your project type</p>
            <div class="gs-quiz__options">
                <div class="gs-quiz__option" data-value="new">
                    <span class="gs-quiz__option-icon">🏗️</span>
                    <span class="gs-quiz__option-text">New build — Starting from scratch</span>
                </div>
                <div class="gs-quiz__option" data-value="retrofit">
                    <span class="gs-quiz__option-icon">🏚️</span>
                    <span class="gs-quiz__option-text">Retrofit — I have an existing shed/barn</span>
                </div>
                <div class="gs-quiz__option" data-value="unsure">
                    <span class="gs-quiz__option-icon">❓</span>
                    <span class="gs-quiz__option-text">Not sure yet — Exploring options</span>
                </div>
            </div>
            <div class="gs-quiz__nav">
                <button class="gs-quiz__btn gs-quiz__btn--back" disabled>← Back</button>
                <button class="gs-quiz__btn gs-quiz__btn--next" disabled>Next →</button>
            </div>
        </div>

        <!-- Q2 -->
        <div class="gs-quiz__question" data-question="2">
            <h2 class="gs-quiz__q-title">How many horses?</h2>
            <p class="gs-quiz__q-sub">How many horses will this stable accommodate?</p>
            <div class="gs-quiz__options">
                <div class="gs-quiz__option" data-value="1"><span class="gs-quiz__option-icon">1️⃣</span><span class="gs-quiz__option-text">One horse</span></div>
                <div class="gs-quiz__option" data-value="2"><span class="gs-quiz__option-icon">2️⃣</span><span class="gs-quiz__option-text">Two horses</span></div>
                <div class="gs-quiz__option" data-value="3"><span class="gs-quiz__option-icon">3️⃣</span><span class="gs-quiz__option-text">Three horses</span></div>
                <div class="gs-quiz__option" data-value="4"><span class="gs-quiz__option-icon">4️⃣</span><span class="gs-quiz__option-text">Four horses</span></div>
                <div class="gs-quiz__option" data-value="5"><span class="gs-quiz__option-icon">5️⃣+</span><span class="gs-quiz__option-text">Five or more</span></div>
            </div>
            <div class="gs-quiz__nav">
                <button class="gs-quiz__btn gs-quiz__btn--back">← Back</button>
                <button class="gs-quiz__btn gs-quiz__btn--next" disabled>Next →</button>
            </div>
        </div>

        <!-- Q3 -->
        <div class="gs-quiz__question" data-question="3">
            <h2 class="gs-quiz__q-title">What size/type are your horses?</h2>
            <p class="gs-quiz__q-sub">This helps us recommend the right stable size</p>
            <div class="gs-quiz__options">
                <div class="gs-quiz__option" data-value="pony"><span class="gs-quiz__option-icon">🐴</span><span class="gs-quiz__option-text">Pony (under 14 hands)</span></div>
                <div class="gs-quiz__option" data-value="standard"><span class="gs-quiz__option-icon">🐴</span><span class="gs-quiz__option-text">Standard (14–16 hands)</span></div>
                <div class="gs-quiz__option" data-value="large"><span class="gs-quiz__option-icon">🐴</span><span class="gs-quiz__option-text">Large/Draft (16+ hands)</span></div>
                <div class="gs-quiz__option" data-value="maternity"><span class="gs-quiz__option-icon">🤰</span><span class="gs-quiz__option-text">Foaling/Maternity needs</span></div>
                <div class="gs-quiz__option" data-value="mixed"><span class="gs-quiz__option-icon">🔀</span><span class="gs-quiz__option-text">Mixed sizes</span></div>
                <div class="gs-quiz__option" data-value="unsure"><span class="gs-quiz__option-icon">❓</span><span class="gs-quiz__option-text">Not sure yet</span></div>
            </div>
            <div class="gs-quiz__nav">
                <button class="gs-quiz__btn gs-quiz__btn--back">← Back</button>
                <button class="gs-quiz__btn gs-quiz__btn--next" disabled>Next →</button>
            </div>
        </div>

        <!-- Q4 -->
        <div class="gs-quiz__question" data-question="4">
            <h2 class="gs-quiz__q-title">What surface will it sit on?</h2>
            <p class="gs-quiz__q-sub">This affects anchoring recommendations</p>
            <div class="gs-quiz__options">
                <div class="gs-quiz__option" data-value="concrete"><span class="gs-quiz__option-icon">🏗️</span><span class="gs-quiz__option-text">Concrete slab — Already have it</span></div>
                <div class="gs-quiz__option" data-value="dirt"><span class="gs-quiz__option-icon">🟤</span><span class="gs-quiz__option-text">Dirt/Gravel pad — Need to prepare</span></div>
                <div class="gs-quiz__option" data-value="soft"><span class="gs-quiz__option-icon">🌱</span><span class="gs-quiz__option-text">Grass/Soft ground — Natural surface</span></div>
                <div class="gs-quiz__option" data-value="undecided"><span class="gs-quiz__option-icon">❓</span><span class="gs-quiz__option-text">Not decided yet</span></div>
            </div>
            <div class="gs-quiz__nav">
                <button class="gs-quiz__btn gs-quiz__btn--back">← Back</button>
                <button class="gs-quiz__btn gs-quiz__btn--next" disabled>Next →</button>
            </div>
        </div>

        <!-- Q5 -->
        <div class="gs-quiz__question" data-question="5">
            <h2 class="gs-quiz__q-title">What's your biggest climate concern?</h2>
            <p class="gs-quiz__q-sub">We'll suggest the right upgrades for your conditions</p>
            <div class="gs-quiz__options">
                <div class="gs-quiz__option" data-value="hot"><span class="gs-quiz__option-icon">☀️</span><span class="gs-quiz__option-text">Hot summers — Need maximum airflow</span></div>
                <div class="gs-quiz__option" data-value="wet"><span class="gs-quiz__option-icon">☔</span><span class="gs-quiz__option-text">Heavy rain — Need weather protection</span></div>
                <div class="gs-quiz__option" data-value="windy"><span class="gs-quiz__option-icon">🌬️</span><span class="gs-quiz__option-text">Strong winds — Need solid sides</span></div>
                <div class="gs-quiz__option" data-value="balanced"><span class="gs-quiz__option-icon">✅</span><span class="gs-quiz__option-text">Mild/Balanced — Standard coverage fine</span></div>
            </div>
            <div class="gs-quiz__nav">
                <button class="gs-quiz__btn gs-quiz__btn--back">← Back</button>
                <button class="gs-quiz__btn gs-quiz__btn--next" disabled>Next →</button>
            </div>
        </div>

        <!-- Q6 -->
        <div class="gs-quiz__question" data-question="6">
            <h2 class="gs-quiz__q-title">Planning any additions?</h2>
            <p class="gs-quiz__q-sub">This helps with layout recommendations</p>
            <div class="gs-quiz__options">
                <div class="gs-quiz__option" data-value="tack"><span class="gs-quiz__option-icon">🏠</span><span class="gs-quiz__option-text">Yes — Want tack room on the side</span></div>
                <div class="gs-quiz__option" data-value="wash"><span class="gs-quiz__option-icon">🚿</span><span class="gs-quiz__option-text">Yes — Want wash bay attached</span></div>
                <div class="gs-quiz__option" data-value="both"><span class="gs-quiz__option-icon">🏗️</span><span class="gs-quiz__option-text">Yes — Want both eventually</span></div>
                <div class="gs-quiz__option" data-value="no"><span class="gs-quiz__option-icon">❌</span><span class="gs-quiz__option-text">No — Just stables for now</span></div>
                <div class="gs-quiz__option" data-value="maybe"><span class="gs-quiz__option-icon">❓</span><span class="gs-quiz__option-text">Maybe later — Not sure yet</span></div>
            </div>
            <div class="gs-quiz__nav">
                <button class="gs-quiz__btn gs-quiz__btn--back">← Back</button>
                <button class="gs-quiz__btn gs-quiz__btn--next" disabled>Calculate &amp; Get My Recommendation →</button>
            </div>
        </div>

    </div><!-- /.gs-quiz__questions -->

    <div class="gs-quiz__loading" id="gs-loading" hidden>
        <div class="gs-quiz__spinner"></div>
        <p>Calculating your perfect match&hellip;</p>
    </div>

    <div class="gs-quiz__results" id="gs-results" hidden></div>

</div><!-- /.gs-quiz -->
