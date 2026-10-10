@extends('layouts.app')

@section('title', 'Playing: ' . $quiz->title . ' - CricBail')
@section('robots', 'noindex, nofollow')

@push('styles')
<style>
    .quiz-timer-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 1.25rem;
        font-weight: 700;
        min-width: 110px;
        text-align: center;
        letter-spacing: 1px;
    }
    .timer-warning {
        background-color: #ef4444 !important;
        color: #ffffff !important;
        animation: pulseTimer 1s infinite;
    }
    @keyframes pulseTimer {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.03); }
    }
    .option-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #e2e8f0;
        background-color: #ffffff;
    }
    .option-card:hover {
        border-color: #0f382c;
        background-color: #f8fafc;
        transform: translateY(-2px);
    }
    .option-card.selected {
        border-color: #0f382c !important;
        background-color: #f0fdf4 !important;
        box-shadow: 0 4px 12px rgba(15, 56, 44, 0.1);
    }
    .option-card.selected .option-letter-badge {
        background-color: #0f382c !important;
        color: #ffffff !important;
    }
    .option-letter-badge {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #f1f5f9;
        font-weight: 700;
        color: #334155;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    .q-palette-btn {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        font-weight: 700;
        font-size: 0.875rem;
        cursor: pointer;
        border: 2px solid #cbd5e1;
        background-color: #ffffff;
        color: #475569;
        transition: all 0.15s ease;
    }
    .q-palette-btn:hover {
        border-color: #0f382c;
    }
    .q-palette-btn.current {
        border-color: #0f382c !important;
        box-shadow: 0 0 0 3px rgba(15, 56, 44, 0.25);
    }
    .q-palette-btn.answered {
        background-color: #0f382c !important;
        color: #ffffff !important;
        border-color: #0f382c !important;
    }
    .q-palette-btn.unanswered {
        background-color: #ffffff;
    }
</style>
@endpush

@section('content')
<div class="container py-3 py-md-4">
    <!-- Top Sticky Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 sticky-top bg-white" style="top: 70px; z-index: 1020;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <h1 class="h5 fw-bold text-dark mb-0">{{ $quiz->title }}</h1>
                    <span class="small text-muted" id="question-tracker-text">
                        Question <span id="current-q-number" class="fw-bold text-dark">1</span> of 10
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div id="quiz-timer" class="badge bg-dark px-3 py-2 rounded-pill quiz-timer-badge text-warning shadow-sm">
                        ⏱️ <span id="timer-display">02:00</span>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-bold" id="btn-header-submit">
                        Submit Quiz
                    </button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress" style="height: 6px;">
                <div id="quiz-progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 10%;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    <!-- Main Question Box -->
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <form id="quiz-form" method="POST" action="{{ route('quizzes.submit', $quiz->slug) }}">
                @csrf
                <input type="hidden" name="attempt_token" value="{{ $attempt->attempt_token }}">

                <!-- Question Viewport -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 p-md-5 bg-white position-relative" id="question-card">
                    <!-- Question Header Badge -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill fs-7 fw-bold" id="question-step-badge">
                            QUESTION 1 OF 10
                        </span>
                        <span class="small text-muted" id="answer-status-pill">
                            ⚪ Not answered yet
                        </span>
                    </div>

                    <!-- Question Text -->
                    <h2 class="h4 fw-bold text-dark mb-4 lh-base" id="question-title">
                        Loading question...
                    </h2>

                    <!-- 4 Multiple Choice Option Cards -->
                    <div class="d-flex flex-column gap-3 mb-4" id="options-container">
                        <!-- Option A -->
                        <label class="option-card p-3 p-md-3.5 rounded-3 d-flex align-items-center gap-3" for="opt-a">
                            <input type="radio" class="d-none option-radio" name="active_radio" id="opt-a" value="a">
                            <span class="option-letter-badge">A</span>
                            <span class="option-text fw-medium text-dark flex-grow-1" id="text-opt-a">Option A</span>
                        </label>

                        <!-- Option B -->
                        <label class="option-card p-3 p-md-3.5 rounded-3 d-flex align-items-center gap-3" for="opt-b">
                            <input type="radio" class="d-none option-radio" name="active_radio" id="opt-b" value="b">
                            <span class="option-letter-badge">B</span>
                            <span class="option-text fw-medium text-dark flex-grow-1" id="text-opt-b">Option B</span>
                        </label>

                        <!-- Option C -->
                        <label class="option-card p-3 p-md-3.5 rounded-3 d-flex align-items-center gap-3" for="opt-c">
                            <input type="radio" class="d-none option-radio" name="active_radio" id="opt-c" value="c">
                            <span class="option-letter-badge">C</span>
                            <span class="option-text fw-medium text-dark flex-grow-1" id="text-opt-c">Option C</span>
                        </label>

                        <!-- Option D -->
                        <label class="option-card p-3 p-md-3.5 rounded-3 d-flex align-items-center gap-3" for="opt-d">
                            <input type="radio" class="d-none option-radio" name="active_radio" id="opt-d" value="d">
                            <span class="option-letter-badge">D</span>
                            <span class="option-text fw-medium text-dark flex-grow-1" id="text-opt-d">Option D</span>
                        </label>
                    </div>

                    <!-- Clear Selection Link -->
                    <div class="d-flex justify-content-end mb-4">
                        <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none" id="btn-clear-selection">
                            ✕ Clear Selection
                        </button>
                    </div>

                    <!-- Step Navigation Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="btn-prev" disabled>
                            &larr; Previous
                        </button>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" id="btn-next">
                                Next Question &rarr;
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Hidden form inputs for all question answers -->
                <div id="hidden-answers-inputs">
                    <!-- Populated dynamically: <input type="hidden" name="answers[12]" value="b"> -->
                </div>
            </form>

            <!-- Question Palette (Quick Jump) -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 fw-bold text-dark mb-0">Question Navigation Palette</h3>
                    <div class="small">
                        <span class="badge bg-success me-2">● Answered: <span id="answered-counter">0</span>/10</span>
                        <span class="badge bg-light text-muted border">○ Unanswered: <span id="unanswered-counter">10</span>/10</span>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center" id="question-palette">
                    @for($i = 1; $i <= 10; $i++)
                    <button type="button" class="q-palette-btn {{ $i === 1 ? 'current' : '' }}" data-index="{{ $i - 1 }}">
                        {{ $i }}
                    </button>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submit Confirmation Modal -->
<div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-labelledby="submitConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold text-dark" id="submitConfirmModalLabel">Ready to Complete Your Quiz?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="display-5 mb-3" id="modal-icon">🏁</div>
                <h4 class="h5 fw-bold text-dark mb-2" id="modal-heading">Submitting Quiz Attempt</h4>
                <p class="text-secondary mb-3" id="modal-message">
                    You have answered all questions. Once submitted, your final score will be calculated on the server.
                </p>
                <div id="modal-unanswered-warning" class="alert alert-warning text-start small d-none">
                    ⚠️ You have <strong id="modal-unanswered-count">0</strong> unanswered question(s). Unanswered questions will receive 0 points.
                </div>
            </div>
            <div class="modal-footer border-top p-3 justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                    Review Questions
                </button>
                <button type="button" class="btn btn-success px-4 fw-bold" id="btn-modal-confirm-submit">
                    Confirm & Submit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Submit Timeout Overlay Modal -->
<div class="modal fade" id="timeoutModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow text-center p-4">
            <div class="display-3 mb-3">⏰</div>
            <h3 class="h4 fw-bold text-danger mb-2">Time's Up!</h3>
            <p class="text-secondary mb-3">
                Your 120-second time limit has expired. Submitting your selected answers now...
            </p>
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Submitting...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // 1. Data initialization (CLIENT-SAFE: NO correct answers or explanations!)
    const questions = @json($questions);
    let userAnswers = @json($savedAnswers); // Format: { question_id: 'a' }
    let remainingSeconds = parseInt({{ $remainingSeconds }}, 10) || 120;
    let currentIndex = 0;
    let isSubmitting = false;

    const totalQuestions = questions.length;

    // 2. Timer Countdown Engine
    const timerDisplay = $('#timer-display');
    const timerBadge = $('#quiz-timer');

    function formatTime(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    timerDisplay.text(formatTime(remainingSeconds));

    const countdownInterval = setInterval(function () {
        if (isSubmitting) return;

        remainingSeconds--;

        if (remainingSeconds <= 30) {
            timerBadge.addClass('timer-warning');
        }

        if (remainingSeconds <= 0) {
            clearInterval(countdownInterval);
            timerDisplay.text('00:00');
            handleTimeExpiration();
            return;
        }

        timerDisplay.text(formatTime(remainingSeconds));
    }, 1000);

    // 3. Question Rendering Function
    function renderQuestion(index) {
        if (index < 0 || index >= totalQuestions) return;

        currentIndex = index;
        const q = questions[index];
        const qNum = index + 1;

        // Update Tracker
        $('#current-q-number').text(qNum);
        $('#question-step-badge').text('QUESTION ' + qNum + ' OF ' + totalQuestions);
        $('#quiz-progress-bar').css('width', ((qNum / totalQuestions) * 100) + '%');

        // Update Title & Options
        $('#question-title').text(q.question_text);
        $('#text-opt-a').text(q.options.a);
        $('#text-opt-b').text(q.options.b);
        $('#text-opt-c').text(q.options.c);
        $('#text-opt-d').text(q.options.d);

        // Reset radio buttons and selection states
        $('.option-radio').prop('checked', false);
        $('.option-card').removeClass('selected');

        // Check if user previously selected an answer for this question
        const currentAnswer = userAnswers[q.id];
        if (currentAnswer && ['a', 'b', 'c', 'd'].includes(currentAnswer)) {
            $('#opt-' + currentAnswer).prop('checked', true);
            $('#opt-' + currentAnswer).closest('.option-card').addClass('selected');
            $('#answer-status-pill').html('<span class="text-success fw-bold">✓ Answered (Option ' + currentAnswer.toUpperCase() + ')</span>');
        } else {
            $('#answer-status-pill').html('<span class="text-muted">⚪ Not answered yet</span>');
        }

        // Navigation button states
        $('#btn-prev').prop('disabled', index === 0);

        if (index === totalQuestions - 1) {
            $('#btn-next').html('Review & Submit 🏁');
        } else {
            $('#btn-next').html('Next Question &rarr;');
        }

        // Update Palette styling
        updatePalette();
    }

    // 4. Update Navigation Palette Pills
    function updatePalette() {
        let answeredCount = 0;

        $('.q-palette-btn').each(function () {
            const btnIdx = parseInt($(this).data('index'), 10);
            const q = questions[btnIdx];

            $(this).removeClass('current answered unanswered');

            if (btnIdx === currentIndex) {
                $(this).addClass('current');
            }

            if (q && userAnswers[q.id]) {
                $(this).addClass('answered');
                answeredCount++;
            } else {
                $(this).addClass('unanswered');
            }
        });

        $('#answered-counter').text(answeredCount);
        $('#unanswered-counter').text(totalQuestions - answeredCount);
    }

    // 5. User Selection Handlers
    $('.option-card').on('click', function () {
        if (isSubmitting) return;

        const radio = $(this).find('.option-radio');
        const selectedValue = radio.val();
        const currentQuestion = questions[currentIndex];

        $('.option-card').removeClass('selected');
        $(this).addClass('selected');
        radio.prop('checked', true);

        // Record answer
        userAnswers[currentQuestion.id] = selectedValue;
        $('#answer-status-pill').html('<span class="text-success fw-bold">✓ Answered (Option ' + selectedValue.toUpperCase() + ')</span>');

        updatePalette();
    });

    $('#btn-clear-selection').on('click', function () {
        if (isSubmitting) return;

        const currentQuestion = questions[currentIndex];
        delete userAnswers[currentQuestion.id];

        $('.option-radio').prop('checked', false);
        $('.option-card').removeClass('selected');
        $('#answer-status-pill').html('<span class="text-muted">⚪ Not answered yet</span>');

        updatePalette();
    });

    // 6. Navigation Buttons
    $('#btn-prev').on('click', function () {
        if (currentIndex > 0) {
            renderQuestion(currentIndex - 1);
        }
    });

    $('#btn-next').on('click', function () {
        if (currentIndex < totalQuestions - 1) {
            renderQuestion(currentIndex + 1);
        } else {
            // Reached last question, prompt submit
            openSubmitModal();
        }
    });

    $('.q-palette-btn').on('click', function () {
        const targetIndex = parseInt($(this).data('index'), 10);
        renderQuestion(targetIndex);
    });

    // 7. Submission Preparation and Execution
    function prepareAndSubmitForm() {
        if (isSubmitting) return;
        isSubmitting = true;

        clearInterval(countdownInterval);

        // Disable all buttons and show loading state
        $('button').prop('disabled', true);
        $('#btn-modal-confirm-submit').html('<span class="spinner-border spinner-border-sm me-2"></span>Submitting...');

        // Build hidden form inputs
        const hiddenContainer = $('#hidden-answers-inputs');
        hiddenContainer.empty();

        $.each(userAnswers, function (questionId, answer) {
            $('<input>').attr({
                type: 'hidden',
                name: 'answers[' + questionId + ']',
                value: answer
            }).appendTo(hiddenContainer);
        });

        $('#quiz-form').submit();
    }

    function handleTimeExpiration() {
        const timeoutModal = new bootstrap.Modal(document.getElementById('timeoutModal'));
        timeoutModal.show();
        setTimeout(function () {
            prepareAndSubmitForm();
        }, 1200);
    }

    function openSubmitModal() {
        let answeredCount = 0;
        $.each(questions, function (i, q) {
            if (userAnswers[q.id]) {
                answeredCount++;
            }
        });
        const unansweredCount = totalQuestions - answeredCount;

        if (unansweredCount > 0) {
            $('#modal-unanswered-count').text(unansweredCount);
            $('#modal-unanswered-warning').removeClass('d-none');
            $('#modal-message').text('You have unanswered questions. Are you sure you want to finish your attempt?');
        } else {
            $('#modal-unanswered-warning').addClass('d-none');
            $('#modal-message').text('Great job! You answered all 10 questions. Ready to see your scorecard and breakdown?');
        }

        const modal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));
        modal.show();
    }

    $('#btn-header-submit').on('click', function () {
        openSubmitModal();
    });

    $('#btn-modal-confirm-submit').on('click', function () {
        prepareAndSubmitForm();
    });

    // Initial render
    renderQuestion(0);
});
</script>
@endpush
