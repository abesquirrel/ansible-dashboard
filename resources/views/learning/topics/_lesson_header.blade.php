{{--
    Shared Learning Lesson Header Partial
    Usage: @include('learning.topics._lesson_header', ['currentSlug' => 'basics'])
--}}
@php
    $lessons = [
        ['slug' => 'basics',          'label' => 'Core Concepts',      'num' => 1],
        ['slug' => 'inventory-adhoc', 'label' => 'Inventory & Ad-Hoc', 'num' => 2],
        ['slug' => 'playbooks',       'label' => 'Playbooks',          'num' => 3],
        ['slug' => 'roles',           'label' => 'Roles',              'num' => 4],
        ['slug' => 'vars-templates',  'label' => 'Vars & Templates',   'num' => 5],
    ];
    $currentIndex = collect($lessons)->search(fn($l) => $l['slug'] === $currentSlug);
@endphp

<style>
/* ── Lesson Progress Stepper ── */
.lesson-stepper {
    display: flex;
    align-items: center;
    gap: 0;
    overflow-x: auto;
    padding-bottom: 2px;
    scrollbar-width: none;
}
.lesson-stepper::-webkit-scrollbar { display: none; }

.lesson-step {
    display: flex;
    align-items: center;
    gap: 0;
    text-decoration: none;
    flex-shrink: 0;
}
.lesson-step-dot {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 1.5px solid var(--border-bright);
    background: var(--bg-surface);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    transition: all .2s;
    position: relative;
    z-index: 1;
}
.lesson-step.active .lesson-step-dot {
    border-color: var(--accent);
    background: var(--accent);
    color: var(--bg-base);
    box-shadow: 0 0 0 3px rgba(57, 217, 138, 0.2);
}
.lesson-step.done .lesson-step-dot {
    border-color: var(--green-dim);
    background: var(--green-dim);
    color: var(--green);
}
.lesson-step:hover:not(.active) .lesson-step-dot {
    border-color: var(--text-secondary);
    color: var(--text-secondary);
}
.lesson-step-label {
    font-size: 11px;
    color: var(--text-muted);
    margin-left: 6px;
    white-space: nowrap;
    transition: color .2s;
}
.lesson-step.active .lesson-step-label { color: var(--text-primary); font-weight: 600; }
.lesson-step.done .lesson-step-label { color: var(--text-secondary); }
.lesson-step:hover:not(.active) .lesson-step-label { color: var(--text-secondary); }

.lesson-step-line {
    height: 1px;
    width: 32px;
    background: var(--border);
    margin: 0 8px;
    flex-shrink: 0;
}
.lesson-step-line.done { background: var(--green-dim); }

/* ── Toast Notification ── */
#copy-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: var(--bg-surface);
    border: 1px solid var(--accent);
    border-radius: var(--radius);
    padding: 8px 16px;
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--accent);
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 9999;
    opacity: 0;
    transform: translateY(8px);
    pointer-events: none;
    transition: opacity .25s, transform .25s;
}
#copy-toast.show {
    opacity: 1;
    transform: translateY(0);
}

/* ── Lab Tasks / Exercise SVG Icon Helpers ── */
.icon-task { color: var(--blue); }
.icon-exercise { color: var(--green); }
.icon-quiz { color: var(--yellow); }
</style>

{{-- Progress Stepper --}}
<div style="margin-bottom: 24px; padding: 16px 0; border-bottom: 1px solid var(--border);">
    <div style="font-size: 10px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: .1em; color: var(--text-muted); margin-bottom: 10px;">Learning Path</div>
    <nav class="lesson-stepper" aria-label="Learning path progress">
        @foreach($lessons as $i => $lesson)
            @php
                $isActive = $lesson['slug'] === $currentSlug;
                $isDone   = $i < $currentIndex;
                $cls      = $isActive ? 'active' : ($isDone ? 'done' : '');
            @endphp
            <a href="{{ route('learning.topic', $lesson['slug']) }}"
               class="lesson-step {{ $cls }}"
               aria-current="{{ $isActive ? 'step' : 'false' }}">
                <div class="lesson-step-dot">
                    @if($isDone)
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                        {{ $lesson['num'] }}
                    @endif
                </div>
                <span class="lesson-step-label">{{ $lesson['label'] }}</span>
            </a>
            @if(!$loop->last)
                <div class="lesson-step-line {{ $isDone ? 'done' : '' }}"></div>
            @endif
        @endforeach
    </nav>
</div>

{{-- Toast container --}}
<div id="copy-toast" role="status" aria-live="polite">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="copy-toast-msg">Copied to clipboard</span>
</div>

<script>
function copyToClipboard(text, label) {
    navigator.clipboard.writeText(text).then(function() {
        const toast = document.getElementById('copy-toast');
        const msg   = document.getElementById('copy-toast-msg');
        msg.textContent = label ? label + ' copied' : 'Copied to clipboard';
        toast.classList.add('show');
        clearTimeout(window._copyToastTimer);
        window._copyToastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
    });
}
</script>
