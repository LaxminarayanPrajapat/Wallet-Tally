document.addEventListener('DOMContentLoaded', function() {
            const featuresBtn = document.getElementById('featuresBtn');
            const wousBtn = document.getElementById('wousBtn');
            
            // Features button scroll
            if (featuresBtn) {
                featuresBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const featuresSection = document.getElementById('features');
                    
                    if (featuresSection) {
                        const navbarHeight = document.querySelector('.navbar').offsetHeight;
                        const targetPosition = featuresSection.offsetTop - navbarHeight - 20;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            }

            // WOUS button scroll
            if (wousBtn) {
                wousBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const testimonialsSection = document.getElementById('testimonials');
                    
                    if (testimonialsSection) {
                        const navbarHeight = document.querySelector('.navbar').offsetHeight;
                        const targetPosition = testimonialsSection.offsetTop - navbarHeight - 20;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            }
        });

