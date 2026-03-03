// Testimonials Fair Rotation Service
// Implements Fisher-Yates shuffle algorithm for fair randomization
// Ensures all testimonials appear over time with rotation queue management

let allTestimonials = [];
let rotationQueue = [];
let displayHistory = [];
let currentPosition = 0;
let rotationInterval = null;
let isPaused = false;
const ITEMS_PER_PAGE = 3;
const ROTATION_DELAY = 5000; // 5 seconds
const HISTORY_SIZE = 10; // Track last 10 displayed to prevent immediate repeats

// Fetch testimonials from API
async function fetchTestimonials() {
    try {
        // Add cache busting parameter
        const response = await fetch('api/get_testimonials.php?t=' + Date.now());
        const data = await response.json();
        allTestimonials = data;

        console.log('Fetched testimonials:', allTestimonials.length);

        if (allTestimonials.length > 0) {
            initializeRotationQueue();
            displayTestimonials();
            startRotation();
        } else {
            showNoTestimonials();
        }
    } catch (error) {
        console.error('Error fetching testimonials:', error);
        showNoTestimonials();
    }
}

// Fisher-Yates shuffle algorithm for fair randomization
// This ensures every testimonial has equal probability of appearing
function shuffleArray(array) {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
}

// Initialize rotation queue with shuffled testimonials
// Creates indices array and shuffles it for fair rotation
function initializeRotationQueue() {
    // Create array of indices [0, 1, 2, ..., n-1]
    const indices = Array.from({ length: allTestimonials.length }, (_, i) => i);

    // Shuffle the indices using Fisher-Yates algorithm
    rotationQueue = shuffleArray(indices);
    currentPosition = 0;

    console.log('Rotation queue initialized with', rotationQueue.length, 'testimonials');
}

// Get next batch of testimonials from the rotation queue
// Ensures all testimonials appear before reshuffling
function getNextBatch() {
    const batch = [];

    // Determine how many items to show (minimum of ITEMS_PER_PAGE or total testimonials)
    const itemsToShow = Math.min(ITEMS_PER_PAGE, allTestimonials.length);

    for (let i = 0; i < itemsToShow; i++) {
        // If we've exhausted the queue, reshuffle and restart
        if (currentPosition >= rotationQueue.length) {
            console.log('Queue exhausted, reshuffling...');
            initializeRotationQueue();
        }

        const testimonialIndex = rotationQueue[currentPosition];
        batch.push(testimonialIndex);
        currentPosition++;
    }

    // Update display history to prevent immediate repeats
    updateDisplayHistory(batch);

    return batch;
}

// Track display history to prevent immediate repeats
// Maintains a rolling window of recently displayed testimonials
function updateDisplayHistory(batch) {
    displayHistory.push(...batch);

    // Keep only the last HISTORY_SIZE items
    if (displayHistory.length > HISTORY_SIZE) {
        displayHistory = displayHistory.slice(-HISTORY_SIZE);
    }
}

// Display current set of testimonials using fair rotation
function displayTestimonials() {
    const container = document.getElementById('testimonialsContainer');
    container.innerHTML = '';

    // Get next batch of testimonials from the fair rotation queue
    const batch = getNextBatch();

    // Remove duplicates from batch (safety check)
    const uniqueBatch = [...new Set(batch)];

    console.log('Displaying', uniqueBatch.length, 'unique testimonials');

    uniqueBatch.forEach((testimonialIndex) => {
        const testimonial = allTestimonials[testimonialIndex];

        const col = document.createElement('div');
        col.className = 'col-md-4 fade-in';

        const stars = '★'.repeat(testimonial.rating) + '☆'.repeat(5 - testimonial.rating);
        const initial = testimonial.username.charAt(0).toUpperCase();
        const date = new Date(testimonial.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short'
        });

        // Determine avatar HTML
        let avatarHTML;
        if (testimonial.profile_picture && testimonial.profile_picture !== 'default') {
            avatarHTML = `<img src="${testimonial.profile_picture}" alt="${escapeHtml(testimonial.username)}" class="testimonial-avatar-img" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                          <div class="testimonial-avatar" style="display:none;">${initial}</div>`;
        } else {
            avatarHTML = `<div class="testimonial-avatar">${initial}</div>`;
        }

        col.innerHTML = `
            <div class="testimonial-card" data-testimonial-id="${testimonial.id}">
                <div class="testimonial-rating">${stars}</div>
                <p class="testimonial-text">"${escapeHtml(testimonial.feedback)}"</p>
                <div class="testimonial-author">
                    ${avatarHTML}
                    <div class="testimonial-info">
                        <h5>${escapeHtml(testimonial.username)}</h5>
                        <small>${date}</small>
                    </div>
                </div>
            </div>
        `;

        // Add hover event listeners
        const card = col.querySelector('.testimonial-card');
        card.addEventListener('mouseenter', pauseRotation);
        card.addEventListener('mouseleave', resumeRotation);

        container.appendChild(col);
    });
}

// Start automatic rotation
function startRotation() {
    if (allTestimonials.length > ITEMS_PER_PAGE) {
        rotationInterval = setInterval(() => {
            if (!isPaused) {
                displayTestimonials();
            }
        }, ROTATION_DELAY);
    }
}

// Pause rotation on hover
function pauseRotation() {
    isPaused = true;
    this.classList.add('paused');
}

// Resume rotation when mouse leaves
function resumeRotation() {
    isPaused = false;
    this.classList.remove('paused');
}

// Show message when no testimonials available
function showNoTestimonials() {
    const container = document.getElementById('testimonialsContainer');
    container.innerHTML = `
        <div class="col-12 text-center">
            <p class="text-muted">No testimonials available yet. Be the first to share your experience!</p>
        </div>
    `;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('testimonialsContainer')) {
        fetchTestimonials();
    }
});
