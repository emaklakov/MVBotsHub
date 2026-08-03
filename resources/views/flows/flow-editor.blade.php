{{-- resources/views/flow-editor.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flow Editor</title>
    <link rel="stylesheet" href="{{ asset('build-flow-editor/' . $css) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body style="margin: 0; padding: 0; height: 100vh; overflow: hidden;">
<div id="flow-editor"
     data-bot-id="{{ $bot }}"
     data-flow-id="{{ $flow }}"
     style="height: 100vh; width: 100vw;">
</div>
<script type="module" src="{{ asset('build-flow-editor/' . $js) }}"></script>
</body>
</html>
