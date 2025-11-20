<?php
$pageTitle = 'AnimaID - Contact';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" href="../assets/logo/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../src/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <a href="../index.html" class="flex items-center space-x-2 text-gray-900 hover:text-blue-600 transition-colors">
                        <img src="../assets/logo/logoHighRes.png" alt="AnimaID Logo" width="32" height="32">
                        <h1 class="text-2xl font-bold">AnimaID</h1>
                    </a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="../index.html" class="text-gray-600 hover:text-blue-600 transition-colors">Home</a>
                    <a href="features.php" class="text-gray-600 hover:text-blue-600 transition-colors">Features</a>
                    <a href="modules.php" class="text-gray-600 hover:text-blue-600 transition-colors">Modules</a>
                    <a href="applets.php" class="text-gray-600 hover:text-blue-600 transition-colors">Applets</a>
                    <a href="api.php" class="text-gray-600 hover:text-blue-600 transition-colors">API</a>
                    <a href="documentation.php" class="text-gray-600 hover:text-blue-600 transition-colors">Documentation</a>
                    <a href="help-center.php" class="text-gray-600 hover:text-blue-600 transition-colors">Help Center</a>
                    <a href="contact.php" class="text-blue-600 font-medium">Contact</a>
                    <a href="privacy.php" class="text-gray-600 hover:text-blue-600 transition-colors">Privacy</a>
                </nav>
                <!-- Unified Theme and Language Switcher -->
                <div class="flex items-center space-x-4">
                    <button id="theme-switcher" class="text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="fas fa-sun"></i>
                    </button>
                    <div id="language-selector-container">
                        <!-- Language selector will be dynamically inserted here by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Contact Form Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Contact Us</h1>
                <p class="text-gray-600">Get in touch with the AnimaID team</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Contact Form -->
                <div>
                    <form id="contact-form" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Your name"
                            >
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="your.email@example.com"
                            >
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                            <select
                                id="subject"
                                name="subject"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select a subject</option>
                                <option value="general">General Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="demo">Request Demo</option>
                                <option value="partnership">Partnership Opportunity</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Your message..."
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            id="submit-button"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors"
                        >
                            <i class="fas fa-paper-plane mr-2"></i>Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-6">Get in Touch</h3>

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-envelope text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Email</h4>
                                <p class="text-gray-600">support@animaid.com</p>
                                <p class="text-gray-600">info@animaid.com</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-clock text-green-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Support Hours</h4>
                                <p class="text-gray-600">Monday - Friday: 9:00 AM - 6:00 PM CET</p>
                                <p class="text-gray-600">Response time: Within 24 hours</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-map-marker-alt text-orange-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Location</h4>
                                <p class="text-gray-600">Rome, Italy</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-globe text-purple-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Online Resources</h4>
                                <p class="text-gray-600">
                                    <a href="documentation.php" class="text-blue-600 hover:text-blue-800">Documentation</a>
                                </p>
                                <p class="text-gray-600">
                                    <a href="help-center.php" class="text-blue-600 hover:text-blue-800">Help Center</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to top -->
        <div class="text-center">
            <a href="../index.html" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Home
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <img src="../assets/logo/logoHighRes.png" alt="AnimaID Logo" width="32" height="32">
                        <h4 class="text-xl font-bold">AnimaID</h4>
                    </div>
                    <p class="text-gray-400 mb-4">
                        A comprehensive management platform for animation centers, connecting staff, activities, and families.
                    </p>
                </div>

                <div>
                    <h5 class="font-semibold mb-4">Platform</h5>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="features.php" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="modules.php" class="hover:text-white transition-colors">Modules</a></li>
                        <li><a href="applets.php" class="hover:text-white transition-colors">Applets</a></li>
                        <li><a href="api.php" class="hover:text-white transition-colors">API</a></li>
                    </ul>
                </div>

                <div>
                    <h5 class="font-semibold mb-4">Support</h5>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="documentation.php" class="hover:text-white transition-colors">Documentation</a></li>
                        <li><a href="help-center.php" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="contact.php" class="hover:text-white transition-colors">Contact</a></li>
                        <li><a href="privacy.php" class="hover:text-white transition-colors">Privacy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 AnimaID. All rights reserved. Version 0.9 - Draft</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/i18next@23.5.1/dist/umd/i18next.min.js"></script>
    <script>
        // Static configuration for AnimaID
        window.ANIMAID_CONFIG = {
            api: {
                baseUrl: 'https://animaidsgn.mywire.org/api',
                port: 443
            },
            system: {
                name: 'AnimaID',
                version: '0.9',
                locale: 'it_IT'
            }
        };
        // Backward compatibility
        window.API_BASE_URL = window.ANIMAID_CONFIG.api.baseUrl;
    </script>
    <script type="module" src="../js/themeLanguageSwitcher.js"></script>
    <script>
        // Contact form handling
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contact-form');
            const submitButton = document.getElementById('submit-button');

            contactForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(contactForm);
                const data = Object.fromEntries(formData);

                // Basic validation
                if (!data.name || !data.email || !data.subject || !data.message) {
                    alert('Please fill in all required fields.');
                    return;
                }

                // Show loading state
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';

                try {
                    // For demo purposes, just show success after a delay
                    // In a real application, you would send this to a server
                    await new Promise(resolve => setTimeout(resolve, 2000));

                    alert('Thank you for your message! We will get back to you soon.');

                    // Reset form
                    contactForm.reset();

                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('There was an error sending your message. Please try again.');
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Message';
                }
            });
        });
    </script>
</body>
</html>
