import Plugin from 'src/plugin-system/plugin.class';

export default class ContactFormRedirectPlugin extends Plugin {

  init() {
    console.log('ContactFormRedirectPlugin initialized');
    this._registerEvents();
  }

  _registerEvents() {
    const submitButton = this.el.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.addEventListener('click', this._onButtonClick.bind(this));
    }
  }

  _onButtonClick(event) {
    event.preventDefault(); // Prevent the default form submission
    console.log('Submit button clicked');

    // Check if form is valid using native browser validation
    if (!this.el.checkValidity()) {
      console.log('Form validation failed (native validation)');
      this.el.reportValidity(); // Display native validation messages
      return; // Stop further execution if form is invalid
    }

    const formData = new FormData(this.el);
    const actionUrl = this.el.getAttribute('action');
    console.log('Submitting to:', actionUrl);

    // Perform the fetch request
    fetch(actionUrl, {
      method: 'POST',
      body: formData
    })
      .then(response => {
        console.log('Fetch response:', response);
        if (response.ok) {
          console.log('Form submitted successfully, redirecting...');
          window.location.href = '/kontakt/confirm/'; // Redirect on success
        } else {
          console.error('Form submission failed with status:', response.status);
        }
      })
      .catch(error => {
        console.error('Error during form submission:', error);
      });
  }
}
