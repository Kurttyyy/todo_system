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
