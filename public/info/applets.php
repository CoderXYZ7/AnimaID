<?php
$pageTitle = 'AnimaID - Applets';
$markdownFile = '../../../docs/Readme.md';
$content = file_exists($markdownFile) ? file_get_contents($markdownFile) : 'Applets documentation not found.';
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
                    <a href="applets.php" class="text-blue-600 font-medium">Applets</a>
                    <a href="applets.php" class="text-gray-600 hover:text-blue-600 transition-colors">Applets</a>
                    <a href="applets.php" class="text-gray-600 hover:text-blue-600 transition-colors">Applets</a>
                    <a href="api.php" class="text-gray-600 hover:text-blue-600 transition-colors">API</a>
                    <a href="documentation.php" class="text-gray-600 hover:text-blue-600 transition-colors">Documentation</a>
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
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <div id="markdown-content">
                <!-- Markdown content will be rendered here -->
            </div>
        </div>

        <!-- Back to top -->
        <div class="text-center mt-8">
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
                        <li><a href="applets.php" class="hover:text-white transition-colors">Applets</a></li>
                        <li><a href="applets.php" class="hover:text-white transition-colors">Applets</a></li>
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
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
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
        // Markdown content
        const markdownContent = `<?php echo addslashes($content); ?>`;

        // Render markdown
        document.addEventListener('DOMContentLoaded', function() {
            const htmlContent = marked.parse(markdownContent);
            document.getElementById('markdown-content').innerHTML = htmlContent;

            // Add some styling to the rendered markdown
            const contentDiv = document.getElementById('markdown-content');

            // Style headings
            contentDiv.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(heading => {
                heading.classList.add('text-gray-900', 'font-semibold', 'mb-4');
                if (heading.tagName === 'H1') heading.classList.add('text-3xl', 'mt-8', 'mb-6');
                else if (heading.tagName === 'H2') heading.classList.add('text-2xl', 'mt-6', 'mb-4');
                else if (heading.tagName === 'H3') heading.classList.add('text-xl', 'mt-4', 'mb-3');
            });

            // Style paragraphs
            contentDiv.querySelectorAll('p').forEach(p => {
                p.classList.add('text-gray-700', 'mb-4', 'leading-relaxed');
            });

            // Style lists
            contentDiv.querySelectorAll('ul, ol').forEach(list => {
                list.classList.add('mb-4');
            });

            contentDiv.querySelectorAll('li').forEach(li => {
                li.classList.add('text-gray-700', 'mb-1');
                if (li.closest('ul')) {
                    li.classList.add('ml-4');
                }
            });

            // Style code blocks
            contentDiv.querySelectorAll('pre').forEach(pre => {
                pre.classList.add('bg-gray-100', 'p-4', 'rounded', 'mb-4', 'overflow-x-auto');
            });

            contentDiv.querySelectorAll('code').forEach(code => {
                code.classList.add('bg-gray-100', 'px-2', 'py-1', 'rounded', 'text-sm');
                if (code.closest('pre')) {
                    code.classList.remove('bg-gray-100', 'px-2', 'py-1', 'rounded');
                }
            });

            // Style links
            contentDiv.querySelectorAll('a').forEach(a => {
                a.classList.add('text-blue-600', 'hover:text-blue-800', 'transition-colors');
            });

            // Style blockquotes
            contentDiv.querySelectorAll('blockquote').forEach(blockquote => {
                blockquote.classList.add('border-l-4', 'border-gray-300', 'pl-4', 'py-2', 'my-4', 'bg-gray-50', 'italic');
            });

            // Style tables
            contentDiv.querySelectorAll('table').forEach(table => {
                table.classList.add('w-full', 'border-collapse', 'mb-4');
            });

            contentDiv.querySelectorAll('th, td').forEach(cell => {
                cell.classList.add('border', 'border-gray-300', 'px-4', 'py-2');
            });

            contentDiv.querySelectorAll('th').forEach(th => {
                th.classList.add('bg-gray-100', 'font-semibold');
            });
        });
    </script>
</body>
</html>
