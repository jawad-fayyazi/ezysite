<!DOCTYPE html>
<html lang="en" id="application">

<head>
    <!-- Meta Tags for SEO and responsiveness -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project_name }}</title>

    <!-- Include GrapesJS Styles -->
    <link href="{{ asset('builder/css/grapes.min.css') }}" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

    <!-- You can add more CSS files as needed -->
    <link rel="stylesheet" href="{{ asset('builder/css/style.css') }}">

    <!-- Additional CSS or any required CSS libraries -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->
</head>

<body>
    <div class="app">

        <!-- Sidebar section (collapsed initially) -->
        <div class="sidenav-collapse gjs-one-bg gjs-two-color">
            <i class="fa-solid fa-bars"> </i>
        </div>

        <div id="side-bar-collpase" class="sidebar gjs-one-bg gjs-two-color">
            <h1 class="project-name" id="test">{{ $project_name }}</h1> <!-- Display project name -->            
            <div class="pages-collapse">
                <i class="fa-solid fa-caret-down"></i> Pages
            </div>
            <div id="page-list">
                <button id="add-page" class="add-page-btn gjs-one-bg gjs-two-color gjs-four-color-h">
                    <i class="fa-solid fa-plus"></i> &nbsp; Add Page
                </button>
                <!-- Dynamic list of pages will go here -->
                <ul id="pages-ul"></ul>
            </div>
        </div>

        <!-- GrapesJS container (builder workspace) -->
        <div id="gjs"></div>

    </div>

    <!-- Include GrapesJS core JS -->
    <script src="{{ asset('builder/js/grapes.min.js') }}"></script>

    <!-- Include necessary GrapesJS plugins -->
    <script src="{{ asset('builder/js/grapesjs-preset-webpage.min.js') }}"></script>
    <script src="{{ asset('builder/js/basic-blocks.js') }}"></script>
    <script src="{{ asset('builder/js/grapesjs-custom-code.js') }}"></script>
    <script src="{{ asset('builder/js/grapesjs-form-blocks.js') }}"></script>
    <script src="https://unpkg.com/grapesjs-component-countdown@1.0.1"></script>
    <script src="https://unpkg.com/grapesjs-plugin-export@1.0.11"></script>
    <script src="https://unpkg.com/grapesjs-tabs@1.0.6"></script>
    <script src="https://unpkg.com/grapesjs-touch@0.1.1"></script>
    <script src="https://unpkg.com/grapesjs-parser-postcss@1.0.1"></script>
    <script src="https://unpkg.com/grapesjs-tooltip@0.1.7"></script>
    <script src="https://unpkg.com/grapesjs-tui-image-editor@0.1.3"></script>
    <script src="https://unpkg.com/grapesjs-typed@1.0.5"></script>
    <script src="https://unpkg.com/grapesjs-style-bg@2.0.1"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>


    <script>
        // Pass PHP variables to JavaScript
        var projectId = {{ $project_id }};
        var projectName = '{{ $project_name }}';
        console.log("Project ID: ", projectId);
        console.log("Project Name: ", projectName);
    </script>
    <!-- Include your custom JS -->
    <script src="{{ asset('builder/js/script.js') }}"></script>

</body>

</html>