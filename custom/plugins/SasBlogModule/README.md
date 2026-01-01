![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1584709250/preview-blog_nn8mcq.jpg)

# Shopware 6 Blog Plugin
## 1. How it looks in the storefront
![](https://sbp-plugin-images.s3.eu-west-1.amazonaws.com/phpZoe2dS)
*Blog listing view*

## 2. How to create a blog entry
After the plugin installation you can find the entity if you hop to `content -> blog`.

![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707400901/Screenshot_2024-02-08_at_20.45.45_t2218w.png)

Here you can see all blog entries and create new ones.
![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1608026832/Bildschirmfoto_2020-12-15_um_12.06.25_ahbgze.png)
*Blog overview page with categories*

And how you can create a new blog entry.
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707400902/Screenshot_2024-02-08_at_20.49.11_jyxewd.png)
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707400903/Screenshot_2024-02-08_at_20.50.20_nqlscf.png)
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707400903/Screenshot_2024-02-08_at_21.00.56_kehqq8.png)
*Blog CMS Detail Page - Here you could fill in the content of your blog entry*

### 3. Configuration
The plugin makes use of two CMS Elements which are part of two different CMS Layouts.
During the plugin installation those two CMS pages will be created for you:
* Blog Listing Page which contains a Blog Listing element
* Blog Detail Page which contains a Blog Detail element

Within the plugin configuration the **Blog Detail Page ID** is assigned,
so Shopware knows which CMS Page to use for the detail page.

#### 1. Menu entry
You need to create a new category within your category tree
and assign the **Blog Listing** CMS Page.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1602580652/Bildschirmfoto_2020-10-13_um_12.16.54_nmtgdw.png)
*Category entry*

After this you will see all blog articles within your menu/category entry in the storefront.

#### 2. CMS Element
You can find new CMS elements under `Block Category` -> `Blog`:
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707402750/Screenshot_2024-02-08_at_21.30.19_jwitk3.png)
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707402750/Screenshot_2024-02-08_at_21.31.03_dgtd0f.png)
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707402751/Screenshot_2024-02-08_at_21.32.15_gobd38.png)

For CMS Blog Listing element you can configure the following:

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1602580706/Bildschirmfoto_2020-10-13_um_12.18.22_bdghy1.png)
*CMS Listing element configuration*

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1602581049/Bildschirmfoto_2020-10-13_um_12.23.42_popsgs.png)
*Pagination within the storefront*

### SEO Url
Within the `Settings > SEO` page you can define the structure of the URL to your blog detail page
where you can also select from all available variables.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1602580850/Bildschirmfoto_2020-10-13_um_12.20.25_xxnrro.png)
*SEO URL template*

### RSS Feed
For access **RSS Feed** url you can use this path `/blog/rss`
Example(`http://Your-domain/blog/rss`)


### Blogs assignment on product detail page
You can assign blogs to products and display them on the product detail page.

By default, the plugin comes with a CMS element called **CMS Blog Assignment**, and this element is a part of the **Product detail page** layout. So you can assign blogs to products and display them on the product detail page without any additional configuration. Alternatively, you can create a new CMS block and assign the **CMS Blog Assignment** element to it.


![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707225127/Screenshot_2024-02-06_at_20.11.22_z8umqc.png)
*CMS Blog Assignment element*

![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707225554/Screenshot_2024-02-06_at_20.13.02_fnbz7k.png)
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707225555/Screenshot_2024-02-06_at_20.13.08_y09pih.png)
*CMS Blog Assignment element configuration*

![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707225555/Screenshot_2024-02-06_at_20.17.43_wcjrqg.png)
*Assigned products to blog*

![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707225555/Screenshot_2024-02-06_at_20.17.15_d0mwdy.png)
*Display blogs on product detail page at Storefront*

You can also display blogs of specific product on the other pages of the storefront by creating a new CMS block and assigning the **CMS Blog Assignment** element to it with an additional configuration like select the product.
![](https://res.cloudinary.com/dpxkgwwxp/image/upload/v1707402976/Screenshot_2024-02-08_at_21.35.56_u58sg5.png)
