
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('videoModal');
    const container = document.getElementById('videoContainer');
    const closeBtn = document.querySelector('.video-modal-close');

    document.querySelectorAll('.tutor-photo').forEach(photo => {
        photo.addEventListener('click', () => {
            const videoSrc = photo.getAttribute('data-video');
            container.innerHTML = '';

            if (videoSrc.includes('youtube.com') || videoSrc.includes('vimeo.com')) {
                const iframe = document.createElement('iframe');
                iframe.src = videoSrc;
                iframe.width = '100%';
                iframe.height = '450';
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allowfullscreen', '1');
                container.appendChild(iframe);
            } else {
                const video = document.createElement('video');
                video.src = videoSrc;
                video.controls = true;
                video.autoplay = true;
                video.style.width = '100%';
                container.appendChild(video);
            }

            modal.style.display = 'flex';
        });
    });

    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        container.innerHTML = '';
    });

    window.addEventListener('click', e => {
        if (e.target === modal) {
            modal.style.display = 'none';
            container.innerHTML = '';
        }
    });
});
