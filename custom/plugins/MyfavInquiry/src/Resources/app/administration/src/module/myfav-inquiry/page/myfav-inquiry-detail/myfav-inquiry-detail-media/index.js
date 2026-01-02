import template from './myfav-inquiry-detail-media.html.twig';
import './myfav-inquiry-detail-media.scss';
import InquiryMediaApiService from '../../../service/api/inquiry-media.api.service';

const { Component, Application, Service } = Shopware;

Component.register('myfav-inquiry-detail-media', {
  template,

  props: {
    mediaId: {
      type: String,
      required: false,
      default: null
    },
  },

  data() {
    return {
      mediaData: null,
      mediaName: 'default-image', // default image name if not available
      inquiryMediaApiService: null,
      isDownloading: false, // Flag to track if a download is in progress
    };
  },

  created() {
    const httpClient = Application.getContainer('init')['httpClient'];
    const loginService = Service('loginService');
    this.inquiryMediaApiService = new InquiryMediaApiService(httpClient, loginService);
    this.getMedia();
  },

  mounted() {
    this.setupDownloadListener();
  },

  methods: {
    blobToBase64(blob) {
      return new Promise((resolve, _) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.readAsDataURL(blob);
      });
    },

    getMedia() {
      this.inquiryMediaApiService.getMedia(this.mediaId).then(response => {
        this.blobToBase64(response.data).then(base64 => {
          this.mediaData = base64;
          // Extract the media name or any other dynamic part from the response, if available
          this.mediaName = response.data.mediaName || 'default-image'; // Adjust as needed
          //console.log(this.mediaData);
        });
      });
    },

    setupDownloadListener() {
      // Listen for a click on the thumbnail
      document.addEventListener('click', (event) => {
        if (event.target.closest('.myfav-inquiry-detail-media .media-thumbnail')) {
          // Prevent download if another download is in progress
          if (this.isDownloading) return;

          // Disable all other thumbnails during the download
          const thumbnails = document.querySelectorAll('.media-thumbnail');
          thumbnails.forEach(thumb => thumb.style.pointerEvents = 'none');

          // Set the flag to true, indicating a download is in progress
          this.isDownloading = true;

          const mediaUrl = this.mediaData; // Base64 image data
          const fileName = `${this.mediaName}-${this.mediaId}.jpg`; // Example: 'image-12345.jpg'

          const link = document.createElement('a');
          link.href = mediaUrl;
          link.download = fileName; // Dynamic filename
          link.click();

          // After download, reset the flag and enable all thumbnails again after a timeout
          setTimeout(() => {
            this.isDownloading = false; // Reset flag
            thumbnails.forEach(thumb => thumb.style.pointerEvents = 'auto'); // Re-enable all thumbnails
          }, 1000);  // 1 second timeout (you can adjust this based on your needs)
        }
      });
    }
  }
});
