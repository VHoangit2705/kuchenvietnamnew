/**
 * Custom Upload Adapter for CKEditor 5
 * Tương thích với Laravel Backend
 */
class CustomUploadAdapter {
    constructor(loader, url, csrfToken) {
        // The file loader instance to use during the upload.
        this.loader = loader;
        // The upload URL.
        this.url = url;
        // CSRF Token for Laravel
        this.csrfToken = csrfToken;
    }

    // Starts the upload process.
    upload() {
        return this.loader.file
            .then(file => new Promise((resolve, reject) => {
                this._initRequest();
                this._initListeners(resolve, reject, file);
                this._sendRequest(file);
            }));
    }

    // Aborts the upload process.
    abort() {
        if (this.xhr) {
            this.xhr.abort();
        }
    }

    // Initializes the XMLHttpRequest object using the URL passed to the constructor.
    _initRequest() {
        const xhr = this.xhr = new XMLHttpRequest();

        xhr.open('POST', this.url, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.responseType = 'json';
    }

    // Initializes XMLHttpRequest listeners.
    _initListeners(resolve, reject, file) {
        const xhr = this.xhr;
        const loader = this.loader;
        const genericErrorText = `Không thể tải ảnh lên hệ thống: ${ file.name }.`;

        xhr.addEventListener('error', () => reject(genericErrorText));
        xhr.addEventListener('abort', () => reject());
        xhr.addEventListener('load', () => {
            const response = xhr.response;

            if (!response || response.error) {
                return reject(response && response.error ? response.error.message : genericErrorText);
            }

            // Nếu thành công, response URL cho editor
            resolve({
                default: response.url
            });
        });

        if (xhr.upload) {
            xhr.upload.addEventListener('progress', evt => {
                if (evt.lengthComputable) {
                    loader.uploadTotal = evt.total;
                    loader.uploaded = evt.loaded;
                }
            });
        }
    }

    // Prepares the data and sends the request.
    _sendRequest(file) {
        // Chuẩn bị form data theo chuẩn Multipart
        const data = new FormData();
        data.append('upload', file);

        // Send the request.
        this.xhr.send(data);
    }
}

// Plugin function để truyền vào cấu hình extraPlugins của CKEditor
function MyCustomUploadAdapterPlugin(editor) {
    // Đảm bảo url upload và token được truyền từ window namespace (nếu có)
    const uploadUrl = window.ckeditorUploadUrl || '';
    const csrfToken = window.ckeditorCsrfToken || '';
    
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        return new CustomUploadAdapter(loader, uploadUrl, csrfToken);
    };
}
