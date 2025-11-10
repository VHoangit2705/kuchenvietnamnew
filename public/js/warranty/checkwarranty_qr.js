const codeReader = new ZXing.BrowserMultiFormatReader();
const videoElement = document.getElementById('video');

document.getElementById('btn_check_qick').addEventListener('click', async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    videoElement.srcObject = stream;
    videoElement.style.display = 'block';
    
    codeReader.decodeFromVideoDevice(null, 'video', (result, err) => {
      if (result) {
          let serial = null;
          const rawData = result.text.trim(); 
          if (rawData.includes('serial=')) {
              const url = new URL(rawData);
              serial = url.searchParams.get('serial');
          } else if (/SN[:\- ]?\s*\d{6,}/i.test(rawData)) {
              const match = rawData.match(/SN[:\- ]?\s*(\d{6,})/i);
              if (match) {
                  serial = match[1];
              }
          } else if (/^\d{6,}$/.test(rawData)) {
              serial = rawData;
          }

          if (serial) {
              OpenWaitBox();        
              $.ajax({
                  url: (window.warrantyFindQrRoute || ''),
                  method: 'POST',
                  data: {
                      serial_number: serial,
                      _token: (window.csrfToken || '')
                  },
                  success: function(response) {
                      CloseWaitBox();
                      if (response.success) {
                          const $form = $('<form>', {
                              method: 'POST',
                              action: (window.warrantyFormCardRoute || '')
                          });

                          $form.append($('<input>', {
                              type: 'hidden',
                              name: '_token',
                              value: (window.csrfToken || '')
                          }));
                          $form.append($('<input>', {
                              type: 'hidden',
                              name: 'warranty',
                              value: JSON.stringify(response.warranty)
                          }));
                          $form.append($('<input>', {
                              type: 'hidden',
                              name: 'lstproduct',
                              value: JSON.stringify(response.lstproduct)
                          }));
                          $('body').append($form);
                          $form.submit();
                      } else {
                          Swal.fire({
                              icon: 'warning',
                              title: response.message,
                              timer: 2000,
                          });
                      }
                  },
                  error: function(xhr) {
                      CloseWaitBox();
                      console.error(xhr.responseJSON?.message || 'Lỗi không xác định');
                  }
              });
          } else {
              alert("Không tìm thấy mã serial hợp lệ trong chuỗi: " + rawData);
          }
          stream.getTracks().forEach(track => track.stop());
          videoElement.srcObject = null;
          videoElement.style.display = 'none';
          codeReader.reset();
      }
      if (err && !(err instanceof ZXing.NotFoundException)) {
        console.error(err);
      }
    });
  } catch (error) {
    alert('Không thể mở camera: ' + error.message);
  }
});


