<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Welcome</title>
        <!-- Add Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Nunito', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: blue; /* Changed background color to blue */
            }
            .message {
                font-size: 24px;
                text-align: center;
                color: white; /* Kept text color white */
                background-color: rgba(255, 0, 0, 0.5); /* Added semi-transparent red background */
                padding: 20px; /* Added padding for better appearance */
                border-radius: 10px; /* Optional: rounded corners for the box */
            }
        </style>
    </head>
    <body>
        <div class="message" id="hoverMessage">
            {{ $message }}
        </div>

        <!-- Move Bootstrap JS to the end of the body -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const hoverMessage = document.getElementById('hoverMessage');
                hoverMessage.addEventListener('mouseenter', function() {
                    this.classList.add('text-primary', 'fw-bold');
                });
                hoverMessage.addEventListener('mouseleave', function() {
                    this.classList.remove('text-primary', 'fw-bold');
                });
            });
        </script>
    </body>
</html>