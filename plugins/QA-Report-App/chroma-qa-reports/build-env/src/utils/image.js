/**
 * Image Processing Utilities
 */

/**
 * Compresses an image file using Canvas.
 * 
 * @param {File} file - The original file
 * @param {number} maxWidth - Max width in pixels
 * @param {number} maxHeight - Max height in pixels
 * @param {number} quality - JPEG quality (0 to 1)
 * @returns {Promise<File>} - A promise that resolves to the compressed File
 */
export const compressImage = (file, maxWidth = 2048, maxHeight = 2048, quality = 0.8) => {
    return new Promise((resolve, reject) => {
        // Use Blob URL instead of Base64 to save memory
        const blobUrl = URL.createObjectURL(file);
        const img = new Image();
        img.src = blobUrl;

        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;

            if (width > height) {
                if (width > maxWidth) {
                    height *= maxWidth / width;
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width *= maxHeight / height;
                    width = maxWidth;
                }
            }

            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            // Clean up source blob
            URL.revokeObjectURL(blobUrl);

            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                } else {
                    reject(new Error('Canvas toBlob failed'));
                }
            }, 'image/jpeg', quality);
        };

        img.onerror = (err) => {
            URL.revokeObjectURL(blobUrl);
            reject(err);
        };
    });
};
