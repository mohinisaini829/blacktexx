import Plugin from 'src/plugin-system/plugin.class';

export default class SantaDiscountModalPlugin extends Plugin {
    init() {
        // Call the function to show the modal after a delay
        this.showDiscountModal();
    }

    showDiscountModal() {
        setTimeout(() => {
            // Select the modal using vanilla JavaScript
            const modal = document.getElementById('santa--discount-modal');

        // Check if the modal exists
        if (modal) {
            $(modal).modal('show'); // Use jQuery to display the modal
        } else {
            console.warn('Santa Discount Modal not found.');
        }
    }, 60 * 1000); // 60 seconds
    }
}
