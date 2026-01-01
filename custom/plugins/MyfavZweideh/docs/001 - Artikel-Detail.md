# 001 - Artikel-Detail

## Start designing - Frontend view in product-detail page.

The design process begins in article detail. The plugin MyfavZweideh extends the biloba variant matrix buttons, to add a popup:

<img src="./img/001 - Article-Detail - Modal.jpg" alt="Screenshot, of the modal, numbers mark the elements, that are described below" />

1) The third button "Hier gestalten" opens the modal.

These buttons are defined in ```custom\plugins\MyfavZweideh\src\Resources\views\storefront\biloba\article-variant-order-matrix\article-variant-order-matrix-button.html.twig```

2) **Projekt-Designer** button opens this lumise designer.

3) **Kleinmengen-Designer** opens a different designer.


## Clicking the "Projekt-Designer"-Button

The functionality of the buttons in the modal can be configured in the plugin documentation. The file that is responsible for the actions in the template is ```custom\plugins\MyfavZweideh\src\Resources\views\storefront\biloba\article-variant-order-matrix\designer-modal-action.html.twig```.

We are looking for this part in this file:

```twig
{% if buttonData.buttonAction == 'openSmallAmountDesigner' %}
        <a href="{{ path('frontend.myfav.designer.start') }}?productId={{ page.product.id }}">
```

When the project designer button is clicked, a controller of the plugin is called first with the route ```frontend.myfav.designer.start```.
The route is defined in the controller ```custom\plugins\MyfavZweideh\src\Storefront\Controller\LumiseDesignerStartController.php```.

