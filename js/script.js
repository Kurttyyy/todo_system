const todayDate = document.getElementById('today-date');

if (todayDate) {
  const updateTodayDate = () => {
    todayDate.textContent = new Intl.DateTimeFormat('en-US', {
      timeZone: 'Asia/Manila',
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric'
    }).format(new Date());
  };

  updateTodayDate();
  setInterval(updateTodayDate, 60000);
}

document.addEventListener('submit', (event) => {
  const form = event.target.closest('form[data-confirm]');

  if (form && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});

const qrDialog = document.getElementById('qr-dialog');
const showQr = document.getElementById('show-qr');
const closeQr = document.getElementById('close-qr');

if (qrDialog && showQr && closeQr) {
  const hideQr = () => {
    qrDialog.hidden = true;
    document.body.classList.remove('modal-open');
    showQr.focus();
  };

  showQr.addEventListener('click', () => {
    qrDialog.hidden = false;
    document.body.classList.add('modal-open');
    closeQr.focus();
  });
  closeQr.addEventListener('click', hideQr);
  qrDialog.addEventListener('click', (event) => {
    if (event.target === qrDialog) hideQr();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !qrDialog.hidden) hideQr();
  });
}
