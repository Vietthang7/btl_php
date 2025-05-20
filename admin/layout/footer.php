            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Custom JS -->
    <script>
    // Auto dismiss alert after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 5000);
        
        // Add fade-in animation to main components
        const cards = document.querySelectorAll('.card');
        cards.forEach(function(card, index) {
            card.style.animationDelay = (index * 0.1) + 's';
            card.classList.add('fade-in');
        });
    });
    </script>
</body>
</html>