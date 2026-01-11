document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.contact-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      // In a real application, you would send this data to a server
      alert('Thank you for your message! We will get back to you soon.');
      form.reset();
    });
  }
});



