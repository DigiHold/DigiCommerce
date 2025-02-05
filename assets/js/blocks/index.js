(() => {
  // blocks/button/edit.js
  var { __ } = wp.i18n;
  var { useBlockProps, InspectorControls } = wp.blockEditor;
  var {
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl
  } = wp.components;
  function ButtonEdit({ attributes, setAttributes }) {
    const { productId, customTitle, showPrice, subtitle, customClass, variationId } = attributes;
    const blockProps = useBlockProps();
    const buttonClasses = ["dc-button", customClass].filter(Boolean);
    const products = window.digicommerceBlocksData?.products || [];
    const currencies = window.digicommerceBlocksData?.currencies || {};
    const selectedCurrency = window.digicommerceBlocksData?.selectedCurrency || "USD";
    const currencyPosition = window.digicommerceBlocksData?.currencyPosition || "left";
    const currencySymbol = currencies[selectedCurrency]?.symbol || "$";
    const selectedProduct = products.find((p) => p.value === productId);
    const productVariations = selectedProduct?.variations || [];
    const hasVariations = productVariations.length > 0;
    const selectedVariation = hasVariations && variationId ? productVariations[parseInt(variationId) - 1] : null;
    const formatPrice = (price, salePrice = null) => {
      if (!price && price !== 0)
        return "";
      const regularPrice = parseFloat(price).toFixed(2);
      let priceStr = regularPrice;
      if (salePrice && parseFloat(salePrice) > 0 && parseFloat(salePrice) < parseFloat(price)) {
        const salePriceFormatted = parseFloat(salePrice).toFixed(2);
        const regularPriceWithCurrency = applyCurrencyFormat(regularPrice);
        const salePriceWithCurrency = applyCurrencyFormat(salePriceFormatted);
        return `${salePriceWithCurrency} <del>${regularPriceWithCurrency}</del>`;
      }
      return applyCurrencyFormat(priceStr);
    };
    const applyCurrencyFormat = (price) => {
      switch (currencyPosition) {
        case "left":
          return `${currencySymbol}${price}`;
        case "right":
          return `${price}${currencySymbol}`;
        case "left_space":
          return `${currencySymbol} ${price}`;
        case "right_space":
          return `${price} ${currencySymbol}`;
        default:
          return `${currencySymbol}${price}`;
      }
    };
    let buttonText = customTitle || selectedProduct?.label || __("Buy Now", "digicommerce");
    if (showPrice && selectedProduct) {
      const productVariations2 = selectedProduct?.variations || [];
      const hasVariations2 = productVariations2.length > 0;
      if (hasVariations2) {
        if (selectedVariation) {
          const price = formatPrice(
            selectedVariation.price,
            selectedVariation.salePrice
          );
          buttonText += ` - ${price}`;
        }
      } else {
        const price = formatPrice(
          selectedProduct.price,
          selectedProduct.sale_price
        );
        buttonText += ` - ${price}`;
      }
    }
    return /* @__PURE__ */ React.createElement(React.Fragment, null, /* @__PURE__ */ React.createElement(InspectorControls, null, /* @__PURE__ */ React.createElement(PanelBody, { title: __("Button Settings", "digicommerce") }, /* @__PURE__ */ React.createElement(
      SelectControl,
      {
        label: __("Select Product", "digicommerce"),
        value: productId || "",
        options: [
          { label: __("Select a product...", "digicommerce"), value: "" },
          ...products
        ],
        onChange: (value) => {
          setAttributes({
            productId: value ? parseInt(value) : null,
            variationId: ""
            // Reset variation when product changes
          });
        },
        __nextHasNoMarginBottom: true
      }
    ), hasVariations && /* @__PURE__ */ React.createElement(
      SelectControl,
      {
        label: __("Select Variation", "digicommerce"),
        value: variationId || "",
        options: [
          { label: __("Select a variation...", "digicommerce"), value: "" },
          ...productVariations.map((v, index) => ({
            label: v.name,
            value: (index + 1).toString()
          }))
        ],
        onChange: (value) => setAttributes({ variationId: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      TextControl,
      {
        label: __("Custom Button Text", "digicommerce"),
        value: customTitle || "",
        onChange: (value) => setAttributes({ customTitle: value }),
        help: __("Leave empty to use product name", "digicommerce"),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      ToggleControl,
      {
        label: __("Show Price", "digicommerce"),
        checked: !!showPrice,
        onChange: (value) => setAttributes({ showPrice: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      TextControl,
      {
        label: __("Subtitle", "digicommerce"),
        value: subtitle || "",
        onChange: (value) => setAttributes({ subtitle: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      TextControl,
      {
        label: __("Custom Class", "digicommerce"),
        value: customClass || "",
        onChange: (value) => setAttributes({ customClass: value }),
        help: __("Add custom CSS classes to the button", "digicommerce"),
        __nextHasNoMarginBottom: true
      }
    ))), /* @__PURE__ */ React.createElement("div", { className: "dc-button-wrapper" }, /* @__PURE__ */ React.createElement(
      "a",
      {
        ...blockProps,
        href: "#",
        className: [...buttonClasses, blockProps.className].filter(Boolean).join(" "),
        onClick: (e) => e.preventDefault()
      },
      /* @__PURE__ */ React.createElement("span", { className: "dc-button-text", dangerouslySetInnerHTML: { __html: buttonText } }),
      subtitle && /* @__PURE__ */ React.createElement("span", { className: "dc-button-subtitle" }, subtitle)
    )));
  }

  // blocks/button/save.js
  var { __: __2 } = wp.i18n;
  var { useBlockProps: useBlockProps2 } = wp.blockEditor;
  function ButtonSave({ attributes, clientId }) {
    const { productId, customTitle, showPrice, subtitle, customClass, variationId } = attributes;
    const blockProps = useBlockProps2.save();
    const buttonClasses = ["dc-button", customClass].filter(Boolean);
    let checkoutUrl = window.digicommerceBlocksData?.checkoutUrl || "/checkout/";
    checkoutUrl = `${checkoutUrl}?id=${productId}`;
    if (variationId) {
      checkoutUrl += `&variation=${variationId}`;
    }
    const products = window.digicommerceBlocksData?.products || [];
    const currencies = window.digicommerceBlocksData?.currencies || {};
    const selectedCurrency = window.digicommerceBlocksData?.selectedCurrency || "USD";
    const currencyPosition = window.digicommerceBlocksData?.currencyPosition || "left";
    const currencySymbol = currencies[selectedCurrency]?.symbol || "$";
    const selectedProduct = products.find((p) => p.value === productId);
    const productVariations = selectedProduct?.variations || [];
    const selectedVariation = variationId ? productVariations[parseInt(variationId) - 1] : null;
    const formatPrice = (price, salePrice = null) => {
      if (!price && price !== 0)
        return "";
      const regularPrice = parseFloat(price).toFixed(2);
      let priceStr = regularPrice;
      if (salePrice && parseFloat(salePrice) > 0 && parseFloat(salePrice) < parseFloat(price)) {
        const salePriceFormatted = parseFloat(salePrice).toFixed(2);
        const regularPriceWithCurrency = applyCurrencyFormat(regularPrice);
        const salePriceWithCurrency = applyCurrencyFormat(salePriceFormatted);
        return `${salePriceWithCurrency} <del>${regularPriceWithCurrency}</del>`;
      }
      return applyCurrencyFormat(priceStr);
    };
    const applyCurrencyFormat = (price) => {
      switch (currencyPosition) {
        case "left":
          return `${currencySymbol}${price}`;
        case "right":
          return `${price}${currencySymbol}`;
        case "left_space":
          return `${currencySymbol} ${price}`;
        case "right_space":
          return `${price} ${currencySymbol}`;
        default:
          return `${currencySymbol}${price}`;
      }
    };
    let buttonText = customTitle || selectedProduct?.label || __2("Buy Now", "digicommerce");
    if (showPrice && selectedProduct) {
      const productVariations2 = selectedProduct?.variations || [];
      const hasVariations = productVariations2.length > 0;
      if (hasVariations) {
        if (selectedVariation) {
          const price = formatPrice(
            selectedVariation.price,
            selectedVariation.salePrice
          );
          buttonText += ` - ${price}`;
        }
      } else {
        const price = formatPrice(
          selectedProduct.price,
          selectedProduct.sale_price
        );
        buttonText += ` - ${price}`;
      }
    }
    return /* @__PURE__ */ React.createElement("div", { className: "dc-button-wrapper" }, /* @__PURE__ */ React.createElement(
      "a",
      {
        ...blockProps,
        href: checkoutUrl,
        className: [...buttonClasses, blockProps.className].filter(Boolean).join(" ")
      },
      /* @__PURE__ */ React.createElement("span", { className: "dc-button-text", dangerouslySetInnerHTML: { __html: buttonText } }),
      subtitle && /* @__PURE__ */ React.createElement("span", { className: "dc-button-subtitle" }, subtitle)
    ));
  }

  // blocks/archives/edit.js
  var { __: __3 } = wp.i18n;
  var { useBlockProps: useBlockProps3, InspectorControls: InspectorControls2 } = wp.blockEditor;
  var {
    PanelBody: PanelBody2,
    RangeControl,
    SelectControl: SelectControl2,
    ToggleControl: ToggleControl2,
    Spinner,
    ComboboxControl,
    FormTokenField
  } = wp.components;
  var { useSelect } = wp.data;
  function ArchivesEdit({ attributes, setAttributes }) {
    const {
      postsPerPage,
      columns,
      showTitle,
      showPrice,
      showButton,
      showPagination,
      selectedCategories,
      selectedTags
    } = attributes;
    const blockProps = useBlockProps3({
      className: "digicommerce-archive digicommerce py-12"
    });
    const { products, isLoading, categories, tags } = useSelect((select) => {
      const { getEntityRecords, isResolving } = select("core");
      const query = {
        per_page: postsPerPage === -1 ? 100 : postsPerPage,
        _embed: true,
        post_type: "digi_product"
      };
      if (selectedCategories?.length > 0) {
        query.digi_product_cat = selectedCategories.join(",");
      }
      if (selectedTags?.length > 0) {
        query.digi_product_tag = selectedTags.join(",");
      }
      return {
        products: getEntityRecords("postType", "digi_product", query),
        isLoading: isResolving("core", "getEntityRecords", ["postType", "digi_product", query]),
        categories: getEntityRecords("taxonomy", "digi_product_cat", { per_page: -1 }) || [],
        tags: getEntityRecords("taxonomy", "digi_product_tag", { per_page: -1 }) || []
      };
    }, [postsPerPage, selectedCategories, selectedTags]);
    const categoryOptions = categories?.map((cat) => ({
      label: cat.name,
      value: cat.id.toString()
    })) || [];
    const tagOptions = tags?.map((tag) => ({
      label: tag.name,
      value: tag.id.toString()
    })) || [];
    const renderPrice = (product) => {
      const priceMode = product.meta?.digi_price_mode || "single";
      const singlePrice = product.meta?.digi_price;
      const salePrice = product.meta?.digi_sale_price;
      const priceVariations = product.meta?.digi_price_variations;
      if (priceMode === "single" && singlePrice) {
        if (salePrice && parseFloat(salePrice) < parseFloat(singlePrice)) {
          return /* @__PURE__ */ React.createElement("div", { className: "product-prices" }, /* @__PURE__ */ React.createElement("span", { className: "normal-price" }, formatPrice(salePrice)), /* @__PURE__ */ React.createElement("span", { className: "regular-price" }, formatPrice(singlePrice)));
        }
        return /* @__PURE__ */ React.createElement("span", { className: "normal-price" }, formatPrice(singlePrice));
      }
      if (priceMode === "variations" && priceVariations?.length) {
        const prices = priceVariations.map((v) => ({
          regular: parseFloat(v.price) || 0,
          sale: parseFloat(v.salePrice) || 0
        }));
        const lowestRegular = Math.min(...prices.map((p) => p.regular));
        const validSalePrices = prices.filter((p) => p.sale && p.sale < p.regular);
        const lowestSale = validSalePrices.length > 0 ? Math.min(...validSalePrices.map((p) => p.sale)) : null;
        if (lowestSale) {
          return /* @__PURE__ */ React.createElement("div", { className: "product-prices" }, /* @__PURE__ */ React.createElement("span", { className: "from" }, __3("From:", "digicommerce")), /* @__PURE__ */ React.createElement("span", { className: "normal-price" }, formatPrice(lowestSale)), /* @__PURE__ */ React.createElement("span", { className: "regular-price" }, formatPrice(lowestRegular)));
        }
        return /* @__PURE__ */ React.createElement("div", { className: "product-prices" }, /* @__PURE__ */ React.createElement("span", { className: "from" }, __3("From:", "digicommerce")), /* @__PURE__ */ React.createElement("span", { className: "price" }, formatPrice(lowestRegular)));
      }
      return null;
    };
    const formatPrice = (price) => {
      const currencies = window.digicommerceBlocksData?.currencies || {};
      const selectedCurrency = window.digicommerceBlocksData?.selectedCurrency || "USD";
      const currencyPosition = window.digicommerceBlocksData?.currencyPosition || "left";
      const currencySymbol = currencies[selectedCurrency]?.symbol || "$";
      if (!price && price !== 0)
        return "";
      const formattedPrice = parseFloat(price).toFixed(2);
      switch (currencyPosition) {
        case "left":
          return `${currencySymbol}${formattedPrice}`;
        case "right":
          return `${formattedPrice}${currencySymbol}`;
        case "left_space":
          return `${currencySymbol} ${formattedPrice}`;
        case "right_space":
          return `${formattedPrice} ${currencySymbol}`;
        default:
          return `${currencySymbol}${formattedPrice}`;
      }
    };
    return /* @__PURE__ */ React.createElement(React.Fragment, null, /* @__PURE__ */ React.createElement(InspectorControls2, null, /* @__PURE__ */ React.createElement(PanelBody2, { title: __3("Archive Settings", "digicommerce") }, /* @__PURE__ */ React.createElement("div", { className: "components-base-control" }, /* @__PURE__ */ React.createElement("label", { className: "components-base-control__label" }, __3("Categories", "digicommerce")), /* @__PURE__ */ React.createElement(
      FormTokenField,
      {
        value: selectedCategories ? categoryOptions.filter((cat) => selectedCategories.includes(cat.value)).map((cat) => cat.label) : [],
        suggestions: categoryOptions.map((cat) => cat.label),
        onChange: (tokens) => {
          const newSelectedCategories = tokens.map(
            (token) => categoryOptions.find((cat) => cat.label === token)?.value
          ).filter(Boolean);
          setAttributes({ selectedCategories: newSelectedCategories });
        },
        placeholder: __3("Select categories...", "digicommerce"),
        maxSuggestions: 10
      }
    )), /* @__PURE__ */ React.createElement("div", { className: "components-base-control" }, /* @__PURE__ */ React.createElement("label", { className: "components-base-control__label" }, __3("Tags", "digicommerce")), /* @__PURE__ */ React.createElement(
      FormTokenField,
      {
        value: selectedTags ? tagOptions.filter((tag) => selectedTags.includes(tag.value)).map((tag) => tag.label) : [],
        suggestions: tagOptions.map((tag) => tag.label),
        onChange: (tokens) => {
          const newSelectedTags = tokens.map(
            (token) => tagOptions.find((tag) => tag.label === token)?.value
          ).filter(Boolean);
          setAttributes({ selectedTags: newSelectedTags });
        },
        placeholder: __3("Select tags...", "digicommerce"),
        maxSuggestions: 10
      }
    )), /* @__PURE__ */ React.createElement(
      RangeControl,
      {
        label: __3("Products per page", "digicommerce"),
        value: postsPerPage,
        onChange: (value) => setAttributes({ postsPerPage: value }),
        min: -1,
        max: 100,
        help: __3("-1 shows all products", "digicommerce"),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      RangeControl,
      {
        label: __3("Columns", "digicommerce"),
        value: columns,
        onChange: (value) => setAttributes({ columns: value }),
        min: 1,
        max: 6,
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      ToggleControl2,
      {
        label: __3("Show product title", "digicommerce"),
        checked: showTitle,
        onChange: (value) => setAttributes({ showTitle: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      ToggleControl2,
      {
        label: __3("Show product price", "digicommerce"),
        checked: showPrice,
        onChange: (value) => setAttributes({ showPrice: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      ToggleControl2,
      {
        label: __3("Show product button", "digicommerce"),
        checked: showButton,
        onChange: (value) => setAttributes({ showButton: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ React.createElement(
      ToggleControl2,
      {
        label: __3("Show pagination", "digicommerce"),
        checked: showPagination,
        onChange: (value) => setAttributes({ showPagination: value }),
        __nextHasNoMarginBottom: true
      }
    ))), /* @__PURE__ */ React.createElement("div", { ...blockProps }, isLoading ? /* @__PURE__ */ React.createElement("div", { style: "text-align: center" }, /* @__PURE__ */ React.createElement(Spinner, null)) : products?.length ? /* @__PURE__ */ React.createElement(React.Fragment, null, /* @__PURE__ */ React.createElement("div", { className: `dc-inner col-${columns}` }, products.map((product) => /* @__PURE__ */ React.createElement("article", { key: product.id, className: "product-card" }, /* @__PURE__ */ React.createElement("a", { href: "#", className: "product-link", onClick: (e) => e.preventDefault() }, product._embedded?.["wp:featuredmedia"]?.[0]?.source_url && /* @__PURE__ */ React.createElement("div", { className: "product-img" }, /* @__PURE__ */ React.createElement(
      "img",
      {
        src: product._embedded["wp:featuredmedia"][0].source_url,
        alt: product._embedded["wp:featuredmedia"][0].alt_text || product.title.rendered
      }
    )), /* @__PURE__ */ React.createElement("div", { className: "product-content" }, showTitle && /* @__PURE__ */ React.createElement(
      "h2",
      {
        dangerouslySetInnerHTML: { __html: product.title.rendered }
      }
    ), showPrice && /* @__PURE__ */ React.createElement(React.Fragment, null, renderPrice(product)))), showButton && /* @__PURE__ */ React.createElement("div", { className: "product-button" }, /* @__PURE__ */ React.createElement(
      "a",
      {
        href: "#",
        onClick: (e) => e.preventDefault()
      },
      __3("View Product", "digicommerce")
    ))))), showPagination && /* @__PURE__ */ React.createElement("nav", { className: "pagination" }, /* @__PURE__ */ React.createElement("ul", { className: "page-numbers" }, /* @__PURE__ */ React.createElement("span", { className: "page-numbers current" }, "1"), /* @__PURE__ */ React.createElement("span", { className: "page-numbers" }, "2"), /* @__PURE__ */ React.createElement("span", { className: "page-numbers" }, "3")))) : /* @__PURE__ */ React.createElement("p", { className: "no-product" }, __3("No products found.", "digicommerce"))));
  }

  // resources/js/blocks/index.js
  var { registerBlockType } = wp.blocks;
  var { __: __4 } = wp.i18n;
  registerBlockType("digicommerce/button", {
    apiVersion: 2,
    title: __4("Button", "digicommerce"),
    category: "digicommerce",
    icon: {
      src: /* @__PURE__ */ React.createElement("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 576 512", width: "24", height: "24" }, /* @__PURE__ */ React.createElement("path", { d: "M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" }))
    },
    description: __4("Display a custom product button", "digicommerce"),
    attributes: {
      productId: {
        type: "number"
      },
      customTitle: {
        type: "string"
      },
      showPrice: {
        type: "boolean",
        default: true
      },
      subtitle: {
        type: "string"
      },
      customClass: {
        type: "string"
      },
      variationId: {
        type: "string"
      }
    },
    supports: {
      spacing: {
        margin: true,
        padding: true
      },
      typography: {
        fontSize: true,
        lineHeight: true,
        __experimentalFontFamily: false,
        __experimentalFontStyle: true,
        __experimentalFontWeight: true,
        __experimentalLetterSpacing: true,
        __experimentalTextTransform: true,
        __experimentalTextDecoration: false
      },
      align: ["full", "left", "right", "center"],
      __experimentalBorder: {
        color: true,
        radius: true,
        style: true,
        width: true
      },
      color: {
        text: true,
        background: true,
        gradients: true
      }
    },
    example: {
      attributes: {
        customTitle: __4("My Product", "digicommerce"),
        showPrice: true,
        subtitle: "One-time purchase",
        customClass: ""
      },
      viewportWidth: 400
    },
    edit: ButtonEdit,
    save: ButtonSave
  });
  registerBlockType("digicommerce/archives", {
    apiVersion: 2,
    title: __4("Archives", "digicommerce"),
    category: "digicommerce",
    icon: {
      src: /* @__PURE__ */ React.createElement("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 448 512", width: "24", height: "24" }, /* @__PURE__ */ React.createElement("path", { d: "M88 64c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zM40 32C17.9 32 0 49.9 0 72l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40L40 32zM88 224c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zM40 192c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zm0 192l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zM0 392l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM248 64c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zM200 32c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zm0 192l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zm-40 8l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zm88 152c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zm-48-32c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zM360 64l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zm-40 8l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zm88 152c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zm-48-32c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zm0 192l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zm-40 8l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40z" }))
    },
    description: __4("Display a grid of products with customizable settings", "digicommerce"),
    edit: ArchivesEdit,
    save: () => null
  });
})();
