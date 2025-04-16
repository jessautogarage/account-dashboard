document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('video_input');
    const preview = document.getElementById('video_preview');

    input?.addEventListener('input', () => {
        const url = input.value.trim();
        let embed = '';

        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            let videoIdMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/);
            let videoId = videoIdMatch ? videoIdMatch[1] : null;
            if (videoId) {
                embed = `<iframe width="300" height="180" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`;
            } else {
                embed = "<p>Invalid YouTube URL</p>";
            }
        } else {
            embed = "<p>Unsupported video format. Use a YouTube link.</p>";
        }

        preview.innerHTML = embed;
    });
});
