/**
 * @file
 * Uploads a video file directly to Bunny Stream.
 *
 * @see https://bunny.net/docs/stream/tus-resumable-uploads
 */
((Drupal, once, tus) => {

  /**
   * Fetches the CSRF token for the signature request.
   *
   * @return {Promise<string>}
   *   The token.
   */
  const getCsrfToken = async () => {
    const response = await fetch(Drupal.url('session/token'));
    if (!response.ok) {
      throw new Error(`CSRF token request failed: ${response.status}`);
    }
    return response.text();
  };

  /**
   * Requests a TUS upload from Drupal.
   *
   * @param {string} url
   *   The signature URL.
   *
   * @return {Promise<object>}
   *   The endpoint, library and video IDs, expiry, signature and title.
   */
  const getSignature = async (url) => {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-Token': await getCsrfToken(),
        Accept: 'application/json',
      },
    });
    if (!response.ok) {
      throw new Error(`Signature request failed: ${response.status}`);
    }
    return response.json();
  };

  /**
   * Attaches the uploader.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.bunnyStreamUpload = {
    attach(context) {
      once('bunny-stream-upload', '.bunny-stream-upload', context).forEach(
        (wrapper) => {
          const input = wrapper.querySelector('.bunny-stream-upload__file');
          const progress = wrapper.querySelector(
            '.bunny-stream-upload__progress',
          );
          const cancel = wrapper.querySelector('.bunny-stream-upload__cancel');
          const status = wrapper.querySelector('.bunny-stream-upload__status');
          let upload = null;

          const setStatus = (message, type = 'status') => {
            status.textContent = message;
            status.dataset.type = type;
          };

          const preventUnload = (event) => {
            event.preventDefault();
          };

          const setUploading = (uploading) => {
            input.disabled = uploading;
            cancel.hidden = !uploading;
            progress.hidden = !uploading;
            if (uploading) {
              window.addEventListener('beforeunload', preventUnload);
            } else {
              window.removeEventListener('beforeunload', preventUnload);
            }
          };

          if (!tus.isSupported) {
            input.disabled = true;
            setStatus(
              Drupal.t('Your browser does not support uploading videos.'),
              'error',
            );
            return;
          }

          cancel.addEventListener('click', () => {
            if (upload) {
              // Keep the partial upload so it can be resumed later.
              upload.abort(false);
              upload = null;
            }
            setUploading(false);
            setStatus(
              Drupal.t(
                'Upload paused. Choose the same file again to continue.',
              ),
            );
          });

          input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file) {
              return;
            }

            setUploading(true);
            progress.value = 0;
            setStatus(Drupal.t('Preparing upload…'));

            let signature;
            try {
              signature = await getSignature(wrapper.dataset.signatureUrl);
            } catch (error) {
              setUploading(false);
              setStatus(
                Drupal.t('The upload could not be started. Try again later.'),
                'error',
              );
              return;
            }

            upload = new tus.Upload(file, {
              endpoint: signature.endpoint,
              retryDelays: [0, 3000, 5000, 10000, 20000, 60000],
              headers: {
                AuthorizationSignature: signature.signature,
                AuthorizationExpire: String(signature.expire),
                VideoId: signature.videoId,
                LibraryId: String(signature.libraryId),
              },
              metadata: {
                filetype: file.type,
                title: signature.title,
              },
              // Tie resumable uploads to the Bunny video, so the same file
              // uploaded to another media starts a new upload.
              fingerprint: () =>
                Promise.resolve(
                  [
                    'bunny-stream',
                    signature.videoId,
                    file.name,
                    file.size,
                    file.lastModified,
                  ].join('-'),
                ),
              removeFingerprintOnSuccess: true,
              onProgress: (bytesUploaded, bytesTotal) => {
                const percentage = Math.floor(
                  (bytesUploaded / bytesTotal) * 100,
                );
                progress.value = percentage;
                setStatus(
                  Drupal.t('Uploading… @percentage%', {
                    '@percentage': percentage,
                  }),
                );
              },
              onError: () => {
                upload = null;
                setUploading(false);
                setStatus(
                  Drupal.t(
                    'The upload failed. Choose the same file again to continue.',
                  ),
                  'error',
                );
              },
              onSuccess: () => {
                upload = null;
                setUploading(false);
                input.hidden = true;
                status.textContent = '';
                const message = document.createElement('p');
                message.textContent = Drupal.t(
                  'Upload complete. Bunny Stream is now processing the video.',
                );
                const link = document.createElement('a');
                link.href = wrapper.dataset.mediaUrl;
                link.textContent = Drupal.t('Back to the media');
                status.append(message, link);
                status.dataset.type = 'status';
              },
            });

            const previousUploads = await upload.findPreviousUploads();
            if (previousUploads.length) {
              upload.resumeFromPreviousUpload(previousUploads[0]);
            }
            upload.start();
          });
        },
      );
    },
  };
})(Drupal, once, window.tus);
