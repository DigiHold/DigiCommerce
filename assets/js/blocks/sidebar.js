(() => {
  // resources/js/blocks/sidebar.js
  (function() {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editor;
    const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
    const {
      PanelBody,
      TextControl,
      Button,
      Card,
      CardBody,
      ButtonGroup,
      TextareaControl,
      CheckboxControl,
      SelectControl,
      Slot,
      Modal
    } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const formatFileName = (fileName) => {
      const nameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
      return nameWithoutExt.replace(/-/g, " ");
    };
    const VersionModal = ({ isOpen, onClose, onSave, initialVersion = "", initialChangelog = "" }) => {
      const [version, setVersion] = useState(initialVersion);
      const [changelog, setChangelog] = useState(initialChangelog);
      useEffect(() => {
        if (isOpen) {
          setVersion(initialVersion);
          setChangelog(initialChangelog);
        }
      }, [isOpen, initialVersion, initialChangelog]);
      const handleSave = () => {
        if (!version.trim()) {
          wp.data.dispatch("core/notices").createNotice(
            "error",
            __("Version number is required.", "digicommerce"),
            { type: "snackbar" }
          );
          return;
        }
        const versionRegex = /^\d+\.\d+\.\d+$/;
        if (!versionRegex.test(version.trim())) {
          wp.data.dispatch("core/notices").createNotice(
            "error",
            __("Please use semantic versioning (e.g., 1.0.5)", "digicommerce"),
            { type: "snackbar" }
          );
          return;
        }
        onSave({
          version: version.trim(),
          changelog: changelog.trim(),
          release_date: (/* @__PURE__ */ new Date()).toISOString()
        });
        onClose();
      };
      if (!isOpen)
        return null;
      return /* @__PURE__ */ React.createElement(
        Modal,
        {
          title: initialVersion ? __("Edit Version", "digicommerce") : __("Add Version", "digicommerce"),
          onRequestClose: onClose,
          className: "digi-version-modal"
        },
        /* @__PURE__ */ React.createElement("div", { className: "digi-version-modal-content" }, /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Version Number", "digicommerce"),
            value: version,
            onChange: setVersion,
            placeholder: "1.0.0",
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextareaControl,
          {
            label: __("Changelog", "digicommerce"),
            value: changelog,
            onChange: setChangelog,
            rows: 4,
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement("div", { className: "digi-version-modal-footer" }, /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            isDestructive: true,
            onClick: onClose
          },
          __("Cancel", "digicommerce")
        ), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "primary",
            onClick: handleSave
          },
          __("Save", "digicommerce")
        )))
      );
    };
    const VersionList = ({ versions, onDeleteVersion, onEditVersion }) => {
      return /* @__PURE__ */ React.createElement("div", { className: "digi-version-list" }, versions.map((ver, index) => /* @__PURE__ */ React.createElement(Card, { key: index, className: "digi-version-item" }, /* @__PURE__ */ React.createElement("div", { className: "digi-version-list-header" }, /* @__PURE__ */ React.createElement("div", { className: "digi-version-list-title" }, __("Version", "digicommerce"), " ", ver.version, /* @__PURE__ */ React.createElement("div", { className: "digi-version-actions" }, /* @__PURE__ */ React.createElement(
        Button,
        {
          variant: "secondary",
          onClick: () => onEditVersion(index),
          className: "digi-edit-version"
        },
        /* @__PURE__ */ React.createElement("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 512 512", width: "12", height: "12" }, /* @__PURE__ */ React.createElement("path", { d: "M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z" }))
      ), /* @__PURE__ */ React.createElement(
        Button,
        {
          variant: "secondary",
          isDestructive: true,
          onClick: () => onDeleteVersion(index),
          className: "digi-delete-version"
        },
        /* @__PURE__ */ React.createElement("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 448 512", width: "12", height: "12" }, /* @__PURE__ */ React.createElement("path", { d: "M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z" }))
      )))))));
    };
    const VersionManager = ({ versions = [], onUpdateVersions }) => {
      const [isModalOpen, setIsModalOpen] = useState(false);
      const [editingIndex, setEditingIndex] = useState(null);
      const handleAddVersion = (newVersion) => {
        if (editingIndex !== null) {
          const updatedVersions = [...versions];
          updatedVersions[editingIndex] = newVersion;
          onUpdateVersions(updatedVersions);
          setEditingIndex(null);
        } else {
          const updatedVersions = [...versions, newVersion];
          onUpdateVersions(updatedVersions);
        }
      };
      const handleEditVersion = (index) => {
        setEditingIndex(index);
        setIsModalOpen(true);
      };
      const handleCloseModal = () => {
        setIsModalOpen(false);
        setEditingIndex(null);
      };
      const handleDeleteVersion = (index) => {
        const updatedVersions = versions.filter((_, i) => i !== index);
        onUpdateVersions(updatedVersions);
      };
      return /* @__PURE__ */ React.createElement("div", { className: "digi-version-manager" }, /* @__PURE__ */ React.createElement("div", { className: "digi-version-header" }, /* @__PURE__ */ React.createElement("h3", null, __("Versions", "digicommerce")), /* @__PURE__ */ React.createElement(
        Button,
        {
          variant: "secondary",
          onClick: () => setIsModalOpen(true),
          className: "digi-add-version"
        },
        __("Add", "digicommerce")
      )), /* @__PURE__ */ React.createElement(
        VersionList,
        {
          versions,
          onDeleteVersion: handleDeleteVersion,
          onEditVersion: handleEditVersion
        }
      ), isModalOpen && /* @__PURE__ */ React.createElement(
        VersionModal,
        {
          isOpen: isModalOpen,
          onClose: handleCloseModal,
          onSave: handleAddVersion,
          initialVersion: editingIndex !== null ? versions[editingIndex].version : "",
          initialChangelog: editingIndex !== null ? versions[editingIndex].changelog : ""
        }
      ));
    };
    const CustomURLField = ({ url }) => {
      const [tooltipText, setTooltipText] = useState(__("Click to copy", "digicommerce"));
      const [showTooltip, setShowTooltip] = useState(false);
      const handleCopy = async () => {
        try {
          await navigator.clipboard.writeText(url);
          setTooltipText(__("Link copied", "digicommerce"));
          setTimeout(() => {
            setTooltipText(__("Click to copy", "digicommerce"));
          }, 2e3);
        } catch (err) {
          console.error("Failed to copy:", err);
        }
      };
      return /* @__PURE__ */ React.createElement(
        "div",
        {
          className: "digi-url-field",
          onMouseEnter: () => setShowTooltip(true),
          onMouseLeave: () => setShowTooltip(false)
        },
        /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Direct Purchase URL", "digicommerce"),
            value: url,
            onClick: handleCopy,
            style: { cursor: "pointer" },
            readOnly: true,
            __nextHasNoMarginBottom: true
          }
        ),
        showTooltip && /* @__PURE__ */ React.createElement(
          "div",
          {
            style: {
              position: "absolute",
              top: "100%",
              left: "50%",
              transform: "translateX(-50%)",
              backgroundColor: "#1e1e1e",
              color: "white",
              padding: "6px 12px",
              borderRadius: "4px",
              fontSize: "12px",
              marginTop: "4px",
              zIndex: 1e3,
              pointerEvents: "none",
              whiteSpace: "nowrap"
            }
          },
          tooltipText,
          /* @__PURE__ */ React.createElement(
            "div",
            {
              style: {
                position: "absolute",
                bottom: "100%",
                left: "50%",
                transform: "translateX(-50%)",
                borderLeft: "6px solid transparent",
                borderRight: "6px solid transparent",
                borderBottom: "6px solid #1e1e1e"
              }
            }
          )
        )
      );
    };
    const PriceVariationRow = ({ variation, index, onUpdate, onRemove, onDragStart, onDragOver, onDrop, onDragLeave, onDragEnd }) => {
      const initFileUpload = async () => {
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.multiple = false;
        fileInput.addEventListener("change", async (e) => {
          const file = e.target.files[0];
          if (!file)
            return;
          const formData = new FormData();
          formData.append("action", "digicommerce_upload_file");
          formData.append("file", file);
          formData.append("upload_nonce", digicommerceVars.upload_nonce);
          try {
            if (digicommerceVars.s3_enabled) {
              wp.data.dispatch("core/notices").createNotice(
                "info",
                digicommerceVars.i18n.s3_uploading,
                { type: "snackbar", isDismissible: false }
              );
            } else {
              wp.data.dispatch("core/notices").createNotice(
                "info",
                __("Uploading file...", "digicommerce"),
                { type: "snackbar", isDismissible: false }
              );
            }
            const response = await fetch(digicommerceVars.ajaxurl, {
              method: "POST",
              body: formData
            });
            const data = await response.json();
            if (data.success) {
              const newFile = {
                name: data.data.name,
                file: data.data.file,
                id: data.data.id,
                type: data.data.type,
                size: data.data.size,
                itemName: formatFileName(data.data.name)
              };
              const updatedFiles = [...variation.files || [], newFile];
              onUpdate(index, { ...variation, files: updatedFiles });
              if (digicommerceVars.s3_enabled) {
                wp.data.dispatch("core/notices").createNotice(
                  "success",
                  __("File successfully uploaded to Amazon S3", "digicommerce"),
                  { type: "snackbar" }
                );
              } else {
                wp.data.dispatch("core/notices").createNotice(
                  "success",
                  __("File uploaded successfully", "digicommerce"),
                  { type: "snackbar" }
                );
              }
            } else {
              if (data.data?.s3_error) {
                wp.data.dispatch("core/notices").createNotice(
                  "error",
                  digicommerceVars.i18n.s3_upload_failed,
                  { type: "snackbar" }
                );
              } else {
                throw new Error(data.data || "Upload failed");
              }
            }
          } catch (error) {
            console.error("Upload error:", error);
            wp.data.dispatch("core/notices").createNotice(
              "error",
              __("Upload failed. Please try again.", "digicommerce"),
              { type: "snackbar" }
            );
          }
        });
        fileInput.click();
      };
      const removeFile = async (fileIndex) => {
        const fileToRemove = variation.files[fileIndex];
        const updatedFiles = variation.files.filter((_, i) => i !== fileIndex);
        onUpdate(index, { ...variation, files: updatedFiles });
        try {
          const response = await wp.apiFetch({
            path: "/wp/v2/digicommerce/delete-file",
            method: "POST",
            data: { file: fileToRemove }
          });
          if (response.success) {
            let noticeMessage = response.message;
            if (response.status === "not_found") {
              noticeMessage = __("File removed from variation (was already deleted from server)", "digicommerce");
            }
            wp.data.dispatch("core/notices").createNotice(
              "success",
              noticeMessage,
              { type: "snackbar" }
            );
          }
        } catch (error) {
          console.error("Error deleting file:", error);
          onUpdate(index, { ...variation, files: [...variation.files] });
          wp.data.dispatch("core/notices").createNotice(
            "error",
            error.message || __("Failed to delete file. Please try again.", "digicommerce"),
            { type: "snackbar" }
          );
        }
      };
      const postId = useSelect((select) => select("core/editor").getCurrentPostId());
      const checkoutPageId = digicommerceVars.checkout_page_id || "";
      const getCheckoutUrl = () => {
        if (!checkoutPageId)
          return "";
        return `${wp.url.addQueryArgs(digicommerceVars.checkout_url, {})}`;
      };
      const directUrl = wp.url.addQueryArgs(getCheckoutUrl(), {
        id: postId,
        variation: index + 1
      });
      const handleFileVersionUpdate = (fileIndex, versions) => {
        const updatedFiles = [...variation.files];
        updatedFiles[fileIndex] = {
          ...updatedFiles[fileIndex],
          versions
        };
        onUpdate(index, { ...variation, files: updatedFiles });
      };
      return /* @__PURE__ */ React.createElement(
        Card,
        {
          className: "digi-variation-row digi-row",
          draggable: true,
          onDragStart: (e) => onDragStart(e, index),
          onDragOver: (e) => onDragOver(e),
          onDrop: (e) => onDrop(e, index),
          onDragLeave: (e) => onDragLeave(e),
          onDragEnd: (e) => onDragEnd(e)
        },
        /* @__PURE__ */ React.createElement(CardBody, null, /* @__PURE__ */ React.createElement("div", { className: "digi-inputs" }, /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Name", "digicommerce"),
            value: variation.name,
            onChange: (name) => onUpdate(index, { ...variation, name }),
            placeholder: __("e.g., Single Site License", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Regular Price", "digicommerce"),
            value: variation.price,
            onChange: (value) => {
              if (value === "") {
                onUpdate(index, { ...variation, price: "" });
                return;
              }
              const numValue = parseFloat(value);
              if (!isNaN(numValue)) {
                if (variation.salePrice && parseFloat(variation.salePrice) >= numValue) {
                  onUpdate(index, { ...variation, price: numValue, salePrice: "" });
                } else {
                  onUpdate(index, { ...variation, price: numValue });
                }
              }
            },
            type: "number",
            step: "1",
            min: "0",
            inputMode: "decimal",
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Sale Price", "digicommerce"),
            value: variation.salePrice || "",
            onChange: (value) => {
              if (value === "") {
                onUpdate(index, { ...variation, salePrice: "" });
                return;
              }
              const numValue = parseFloat(value);
              if (!isNaN(numValue)) {
                onUpdate(index, { ...variation, salePrice: numValue });
              }
            },
            onBlur: (e) => {
              const salePriceValue = parseFloat(e.target.value);
              const regularPrice = parseFloat(variation.price);
              if (salePriceValue && regularPrice && salePriceValue >= regularPrice) {
                wp.data.dispatch("core/notices").createNotice(
                  "error",
                  __("Sale price must be less than regular price", "digicommerce"),
                  { type: "snackbar" }
                );
                onUpdate(index, { ...variation, salePrice: "" });
              }
            },
            type: "number",
            step: "1",
            min: "0",
            inputMode: "decimal",
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          CheckboxControl,
          {
            label: __("Selected by default", "digicommerce"),
            checked: variation.isDefault || false,
            onChange: (isChecked) => onUpdate(index, { ...variation, isDefault: isChecked }),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(CustomURLField, { url: directUrl })), /* @__PURE__ */ React.createElement("div", { className: "digi-variation-files" }, variation.files && variation.files.length > 0 && /* @__PURE__ */ React.createElement("p", null, __("Download File:", "digicommerce")), variation.files && variation.files.map((file, fileIndex) => /* @__PURE__ */ React.createElement(Card, { key: fileIndex, className: "digi-card" }, /* @__PURE__ */ React.createElement(CardBody, { className: "digi-card-body" }, /* @__PURE__ */ React.createElement("div", { className: "digi-inputs" }, /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("File Name", "digicommerce"),
            value: file.name,
            onChange: (name) => {
              const updatedFiles = [...variation.files];
              updatedFiles[fileIndex] = { ...file, name };
              onUpdate(index, { ...variation, files: updatedFiles });
            },
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("File Path", "digicommerce"),
            value: file.file,
            disabled: true,
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Item Name", "digicommerce"),
            value: file.itemName || "",
            onChange: (itemName) => {
              const updatedFiles = [...variation.files];
              updatedFiles[fileIndex] = { ...file, itemName };
              onUpdate(index, { ...variation, files: updatedFiles });
            },
            placeholder: __("Enter item name", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        )), digicommerceVars.license_enabled && /* @__PURE__ */ React.createElement("div", { className: "digi-version-section" }, /* @__PURE__ */ React.createElement(
          VersionManager,
          {
            versions: file.versions || [],
            onUpdateVersions: (versions) => handleFileVersionUpdate(fileIndex, versions)
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-file-actions" }, /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            isDestructive: true,
            onClick: () => removeFile(fileIndex)
          },
          __("Remove File", "digicommerce")
        ))))), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            onClick: initFileUpload,
            className: "digi-add-button"
          },
          __("Add Download File", "digicommerce")
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-variation-slots" }, /* @__PURE__ */ React.createElement(
          Slot,
          {
            name: `DigiCommerceVariablePriceAfter-${index}`,
            fillProps: {
              variation,
              index,
              onUpdate
            }
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-actions" }, /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            isDestructive: true,
            onClick: () => onRemove(index),
            className: "digi-remove-button"
          },
          __("Remove Variation", "digicommerce")
        )))
      );
    };
    const FileRow = ({ file, index, onUpdate, onRemove, onDragStart, onDragOver, onDrop, onDragLeave, onDragEnd }) => {
      const handleVersionUpdate = (versions) => {
        onUpdate(index, { ...file, versions });
      };
      return /* @__PURE__ */ React.createElement(
        Card,
        {
          className: "digi-file-row digi-row",
          draggable: true,
          onDragStart: (e) => onDragStart(e, index),
          onDragOver: (e) => onDragOver(e),
          onDrop: (e) => onDrop(e, index),
          onDragLeave: (e) => onDragLeave(e),
          onDragEnd: (e) => onDragEnd(e)
        },
        /* @__PURE__ */ React.createElement(CardBody, null, /* @__PURE__ */ React.createElement("div", { className: "digi-inputs" }, /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("File Name", "digicommerce"),
            value: file.name,
            onChange: (name) => onUpdate(index, { ...file, name }),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("File Path", "digicommerce"),
            value: file.file,
            onChange: (url) => onUpdate(index, { ...file, file: url }),
            disabled: true,
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Item Name", "digicommerce"),
            value: file.itemName || "",
            onChange: (itemName) => {
              const updatedFile = { ...file, itemName };
              onUpdate(index, updatedFile);
            },
            placeholder: __("Enter item name", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        )), digicommerceVars.license_enabled && /* @__PURE__ */ React.createElement("div", { className: "digi-version-section" }, /* @__PURE__ */ React.createElement(
          VersionManager,
          {
            versions: file.versions || [],
            onUpdateVersions: handleVersionUpdate
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-actions" }, /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            isDestructive: true,
            onClick: () => onRemove(index)
          },
          __("Remove File", "digicommerce")
        )))
      );
    };
    const FeaturesRow = ({ feature, index, onUpdate, onRemove, onDragStart, onDragOver, onDrop, onDragLeave, onDragEnd }) => {
      return /* @__PURE__ */ React.createElement(
        Card,
        {
          className: "digi-feature-row digi-row",
          draggable: true,
          onDragStart: (e) => onDragStart(e, index),
          onDragOver: (e) => onDragOver(e),
          onDrop: (e) => onDrop(e, index),
          onDragLeave: (e) => onDragLeave(e),
          onDragEnd: (e) => onDragEnd(e)
        },
        /* @__PURE__ */ React.createElement(CardBody, null, /* @__PURE__ */ React.createElement("div", { className: "digi-inputs" }, /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Name", "digicommerce"),
            value: feature.name,
            onChange: (name) => onUpdate(index, { ...feature, name }),
            placeholder: __("Name", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Text", "digicommerce"),
            value: feature.text,
            onChange: (text) => onUpdate(index, { ...feature, text }),
            placeholder: __("Text", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-actions" }, /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            isDestructive: true,
            onClick: () => onRemove(index),
            className: "digi-remove-button"
          },
          __("Remove Feature", "digicommerce")
        )))
      );
    };
    const UpgradePathPanel = () => {
      const [upgradePaths, setUpgradePaths] = useState([]);
      const [products, setProducts] = useState([]);
      const currentPostId = useSelect((select) => select("core/editor").getCurrentPostId());
      const { editPost } = useDispatch("core/editor");
      const postMeta = useSelect((select) => {
        return select("core/editor").getEditedPostAttribute("meta");
      });
      useEffect(() => {
        wp.apiFetch({
          path: "/wp/v2/digi_product?per_page=-1",
          _fields: "id,title,meta"
        }).then((fetchedProducts) => {
          const licensedProducts = fetchedProducts.filter((product) => {
            return product.meta?.digi_license_enabled === true || product.meta?.digi_price_variations && product.meta.digi_price_variations.some((variation) => variation.license_enabled);
          });
          setProducts(licensedProducts);
        });
      }, []);
      useEffect(() => {
        if (postMeta?.digi_upgrade_paths) {
          setUpgradePaths(postMeta.digi_upgrade_paths);
        }
      }, [postMeta?.digi_upgrade_paths]);
      const addPath = () => {
        const newPath = {
          product_id: "",
          variation_id: "",
          prorate: false,
          include_coupon: false,
          discount_type: "fixed",
          discount_amount: ""
        };
        const updatedPaths = [...upgradePaths, newPath];
        setUpgradePaths(updatedPaths);
        editPost({ meta: { digi_upgrade_paths: updatedPaths } });
      };
      const updatePath = (index, field, value) => {
        const updatedPaths = [...upgradePaths];
        updatedPaths[index] = {
          ...updatedPaths[index],
          [field]: value
        };
        setUpgradePaths(updatedPaths);
        editPost({ meta: { digi_upgrade_paths: updatedPaths } });
      };
      const removePath = (index) => {
        const updatedPaths = upgradePaths.filter((_, i) => i !== index);
        setUpgradePaths(updatedPaths);
        editPost({ meta: { digi_upgrade_paths: updatedPaths } });
      };
      if (!digicommerceVars.pro_active || !digicommerceVars.license_enabled) {
        return null;
      }
      const currentProductEnabled = postMeta?.digi_license_enabled || postMeta?.digi_price_variations && postMeta.digi_price_variations.some((variation) => variation.license_enabled);
      if (!currentProductEnabled) {
        return null;
      }
      return /* @__PURE__ */ React.createElement(PanelBody, { title: __("Upgrade Paths", "digicommerce"), initialOpen: false }, /* @__PURE__ */ React.createElement("div", { className: "digi-container" }, upgradePaths.map((path, index) => /* @__PURE__ */ React.createElement(Card, { key: index, className: "digi-upgrade-path-card" }, /* @__PURE__ */ React.createElement(CardBody, { className: "digi-inputs" }, /* @__PURE__ */ React.createElement(
        SelectControl,
        {
          label: __("Target Product", "digicommerce"),
          value: path.product_id,
          options: [
            { label: __("Select a product...", "digicommerce"), value: "" },
            ...products.map((product) => ({
              label: product.title.rendered,
              value: product.id.toString()
            }))
          ],
          onChange: (value) => updatePath(index, "product_id", value),
          __nextHasNoMarginBottom: true
        }
      ), path.product_id && products.find((p) => p.id === parseInt(path.product_id))?.meta?.digi_price_mode === "variations" && /* @__PURE__ */ React.createElement(
        SelectControl,
        {
          label: __("Target Variation", "digicommerce"),
          value: path.variation_id,
          options: [
            { label: __("Select a variation...", "digicommerce"), value: "" },
            ...products.find((p) => p.id === parseInt(path.product_id)).meta.digi_price_variations.filter((v) => v.license_enabled).map((variation) => ({
              label: variation.name,
              value: variation.id
            }))
          ],
          onChange: (value) => updatePath(index, "variation_id", value),
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ React.createElement(
        CheckboxControl,
        {
          label: __("Prorate", "digicommerce"),
          checked: path.prorate,
          onChange: (value) => updatePath(index, "prorate", value),
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ React.createElement(
        CheckboxControl,
        {
          label: __("Include Coupon", "digicommerce"),
          checked: path.include_coupon,
          onChange: (value) => updatePath(index, "include_coupon", value),
          __nextHasNoMarginBottom: true
        }
      ), path.include_coupon && /* @__PURE__ */ React.createElement(React.Fragment, null, /* @__PURE__ */ React.createElement(
        SelectControl,
        {
          label: __("Discount Type", "digicommerce"),
          value: path.discount_type,
          options: [
            { label: __("Fixed Amount", "digicommerce"), value: "fixed" },
            { label: __("Percentage", "digicommerce"), value: "percentage" }
          ],
          onChange: (value) => updatePath(index, "discount_type", value),
          __nextHasNoMarginBottom: true
        }
      ), /* @__PURE__ */ React.createElement(
        TextControl,
        {
          label: __("Amount", "digicommerce"),
          type: "number",
          value: path.discount_amount,
          onChange: (value) => updatePath(index, "discount_amount", value),
          min: "0",
          step: path.discount_type === "percentage" ? "1" : "0.01",
          __nextHasNoMarginBottom: true
        }
      )), /* @__PURE__ */ React.createElement(
        Button,
        {
          variant: "secondary",
          isDestructive: true,
          onClick: () => removePath(index),
          className: "digi-remove-button"
        },
        __("Remove Path", "digicommerce")
      )))), /* @__PURE__ */ React.createElement(
        Button,
        {
          variant: "primary",
          onClick: addPath,
          className: "digi-add-button"
        },
        __("Add Upgrade Path", "digicommerce")
      )));
    };
    const ApiDataModal = ({ isOpen, onClose, initialData = {}, onSave }) => {
      const [formData, setFormData] = useState({
        homepage: "",
        author: "",
        requires: "",
        requires_php: "",
        tested: "",
        description: "",
        installation: "",
        upgrade_notice: "",
        icons: {
          default: ""
        },
        banners: {
          low: "",
          high: ""
        },
        contributors: [],
        ...initialData
      });
      useEffect(() => {
        if (isOpen) {
          setFormData({
            homepage: "",
            author: "",
            requires: "",
            requires_php: "",
            tested: "",
            description: "",
            installation: "",
            upgrade_notice: "",
            icons: {
              default: ""
            },
            banners: {
              low: "",
              high: ""
            },
            contributors: [],
            ...initialData
          });
        }
      }, [isOpen, initialData]);
      const addContributor = () => {
        setFormData({
          ...formData,
          contributors: [...formData.contributors, {
            username: "",
            avatar: "",
            name: ""
          }]
        });
      };
      const removeContributor = (index) => {
        const newContributors = [...formData.contributors];
        newContributors.splice(index, 1);
        setFormData({
          ...formData,
          contributors: newContributors
        });
      };
      const updateContributor = (index, value) => {
        const newContributors = [...formData.contributors];
        newContributors[index] = value;
        setFormData({
          ...formData,
          contributors: newContributors
        });
      };
      if (!isOpen)
        return null;
      return /* @__PURE__ */ React.createElement(
        Modal,
        {
          title: __("API Data", "digicommerce"),
          onRequestClose: onClose,
          className: "digi-api-modal"
        },
        /* @__PURE__ */ React.createElement("div", { className: "digi-api-modal-content" }, /* @__PURE__ */ React.createElement("div", { className: "digi-api-section" }, /* @__PURE__ */ React.createElement("h3", null, __("Basic Information", "digicommerce")), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Homepage", "digicommerce"),
            type: "url",
            value: formData.homepage,
            onChange: (value) => setFormData({ ...formData, homepage: value }),
            help: __("Plugin homepage URL.", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Author", "digicommerce"),
            value: formData.author,
            onChange: (value) => setFormData({ ...formData, author: value }),
            help: __("Author information with optional link.", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-api-section" }, /* @__PURE__ */ React.createElement("h3", null, __("Requirements", "digicommerce")), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Requires WordPress Version", "digicommerce"),
            value: formData.requires,
            onChange: (value) => setFormData({ ...formData, requires: value }),
            help: __("Minimum required WordPress version.", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Requires PHP Version", "digicommerce"),
            value: formData.requires_php,
            onChange: (value) => setFormData({ ...formData, requires_php: value }),
            help: __("Minimum required PHP version.", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Tested up to", "digicommerce"),
            value: formData.tested,
            onChange: (value) => setFormData({ ...formData, tested: value }),
            help: __("WordPress version the plugin has been tested up to.", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-api-section" }, /* @__PURE__ */ React.createElement("h3", null, __("Description & Installation", "digicommerce")), /* @__PURE__ */ React.createElement(
          TextareaControl,
          {
            label: __("Description", "digicommerce"),
            value: formData.description,
            onChange: (value) => setFormData({ ...formData, description: value }),
            help: __("Full description of the plugin (HTML allowed).", "digicommerce"),
            rows: 4,
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextareaControl,
          {
            label: __("Installation", "digicommerce"),
            value: formData.installation,
            onChange: (value) => setFormData({ ...formData, installation: value }),
            help: __("Installation instructions (HTML allowed).", "digicommerce"),
            rows: 4,
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextareaControl,
          {
            label: __("Upgrade Notice", "digicommerce"),
            value: formData.upgrade_notice,
            onChange: (value) => setFormData({ ...formData, upgrade_notice: value }),
            help: __("Upgrade notices for your users.", "digicommerce"),
            rows: 2,
            __nextHasNoMarginBottom: true
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-api-section" }, /* @__PURE__ */ React.createElement("h3", null, __("Assets", "digicommerce")), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Plugin Icon URL", "digicommerce"),
            type: "url",
            value: formData.icons.default,
            onChange: (value) => setFormData({
              ...formData,
              icons: { default: value }
            }),
            help: __("URL to your plugin's icon (256x256px).", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Banner Low Resolution URL", "digicommerce"),
            type: "url",
            value: formData.banners.low,
            onChange: (value) => setFormData({
              ...formData,
              banners: { ...formData.banners, low: value }
            }),
            help: __("URL to your plugin's low resolution banner (772x250px).", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Banner High Resolution URL", "digicommerce"),
            type: "url",
            value: formData.banners.high,
            onChange: (value) => setFormData({
              ...formData,
              banners: { ...formData.banners, high: value }
            }),
            help: __("URL to your plugin's high resolution banner (1544x500px).", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        )), /* @__PURE__ */ React.createElement("div", { className: "digi-api-section" }, /* @__PURE__ */ React.createElement("h3", null, __("Contributors", "digicommerce")), /* @__PURE__ */ React.createElement("div", { className: "digi-contributor-wrap" }, formData.contributors.map((contributor, index) => /* @__PURE__ */ React.createElement("div", { key: index, className: "digi-contributor-row" }, /* @__PURE__ */ React.createElement("div", { className: "digi-contributor-fields" }, /* @__PURE__ */ React.createElement(
          TextControl,
          {
            value: contributor.username || "",
            onChange: (value) => updateContributor(index, {
              ...contributor,
              username: value
            }),
            placeholder: __("WordPress.org username", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            value: contributor.name || "",
            onChange: (value) => updateContributor(index, {
              ...contributor,
              name: value
            }),
            placeholder: __("Display Name", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            value: contributor.avatar || "",
            onChange: (value) => updateContributor(index, {
              ...contributor,
              avatar: value
            }),
            type: "url",
            placeholder: __("Avatar URL", "digicommerce"),
            __nextHasNoMarginBottom: true
          }
        )), /* @__PURE__ */ React.createElement(
          Button,
          {
            isDestructive: true,
            variant: "secondary",
            onClick: () => removeContributor(index),
            icon: /* @__PURE__ */ React.createElement("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", width: "24", height: "24" }, /* @__PURE__ */ React.createElement("path", { d: "M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z" }))
          }
        ))), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            onClick: () => addContributor(),
            className: "digi-add-contributor"
          },
          __("Add Contributor", "digicommerce")
        ))), /* @__PURE__ */ React.createElement("div", { className: "digi-api-modal-footer" }, /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "secondary",
            isDestructive: true,
            onClick: onClose
          },
          __("Cancel", "digicommerce")
        ), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "primary",
            onClick: () => onSave(formData)
          },
          __("Save", "digicommerce")
        )))
      );
    };
    const ApiDataPanel = () => {
      const [isApiModalOpen, setIsApiModalOpen] = useState(false);
      const { editPost } = useDispatch("core/editor");
      const postMeta = useSelect((select) => {
        return select("core/editor").getEditedPostAttribute("meta");
      });
      if (!digicommerceVars.pro_active || !digicommerceVars.license_enabled) {
        return null;
      }
      return /* @__PURE__ */ React.createElement(PanelBody, { title: __("API Data", "digicommerce"), initialOpen: false }, !postMeta?.digi_api_data || Object.keys(postMeta.digi_api_data).length === 0 ? /* @__PURE__ */ React.createElement(
        Button,
        {
          variant: "primary",
          onClick: () => setIsApiModalOpen(true),
          className: "digi-add-button"
        },
        __("Add API Data", "digicommerce")
      ) : /* @__PURE__ */ React.createElement("div", { className: "digi-api-data-preview" }, /* @__PURE__ */ React.createElement(
        Button,
        {
          variant: "primary",
          onClick: () => setIsApiModalOpen(true)
        },
        __("Edit API Data", "digicommerce")
      ), /* @__PURE__ */ React.createElement("div", { className: "digi-api-data-info" }, /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("strong", null, __("Requires:", "digicommerce")), " WordPress ", postMeta.digi_api_data.requires), /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("strong", null, __("Tested up to:", "digicommerce")), " ", postMeta.digi_api_data.tested))), isApiModalOpen && /* @__PURE__ */ React.createElement(
        ApiDataModal,
        {
          isOpen: isApiModalOpen,
          onClose: () => setIsApiModalOpen(false),
          initialData: postMeta?.digi_api_data,
          onSave: (data) => {
            editPost({ meta: { digi_api_data: data } });
            setIsApiModalOpen(false);
          }
        }
      ));
    };
    const ProductSidebar = () => {
      const [price, setPrice] = useState(0);
      const [salePrice, setSalePrice] = useState("");
      const [files, setFiles] = useState([]);
      const [priceVariations, setPriceVariations] = useState([]);
      const [priceMode, setPriceMode] = useState("single");
      const [productDescription, setProductDescription] = useState("");
      const [features, setFeatures] = useState([]);
      const [instructions, setInstructions] = useState("");
      const { editPost } = useDispatch("core/editor");
      const postId = useSelect((select) => select("core/editor").getCurrentPostId());
      const checkoutPageId = digicommerceVars.checkout_page_id || "";
      const getCheckoutUrl = () => {
        if (!checkoutPageId)
          return "";
        return `${wp.url.addQueryArgs(digicommerceVars.checkout_url, {})}`;
      };
      const postMeta = useSelect((select) => {
        return select("core/editor").getEditedPostAttribute("meta");
      });
      useEffect(() => {
        if (postMeta) {
          setPrice(postMeta.digi_price || 0);
          setSalePrice(postMeta.digi_sale_price || "");
          setFiles(postMeta.digi_files || []);
          setPriceVariations(postMeta.digi_price_variations || []);
          setPriceMode(postMeta.digi_price_mode || "single");
          setProductDescription(postMeta.digi_product_description || "");
          setFeatures(postMeta.digi_features || []);
          setInstructions(postMeta.digi_instructions || "");
        }
      }, [postMeta]);
      const initFileUpload = () => {
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.multiple = false;
        fileInput.addEventListener("change", async (e) => {
          const file = e.target.files[0];
          if (!file)
            return;
          const formData = new FormData();
          formData.append("action", "digicommerce_upload_file");
          formData.append("file", file);
          formData.append("upload_nonce", digicommerceVars.upload_nonce);
          try {
            if (digicommerceVars.s3_enabled) {
              wp.data.dispatch("core/notices").createNotice(
                "info",
                digicommerceVars.i18n.s3_uploading,
                { type: "snackbar", isDismissible: false }
              );
            } else {
              wp.data.dispatch("core/notices").createNotice(
                "info",
                __("Uploading file...", "digicommerce"),
                { type: "snackbar", isDismissible: false }
              );
            }
            const response = await fetch(digicommerceVars.ajaxurl, {
              method: "POST",
              body: formData
            });
            const data = await response.json();
            if (data.success) {
              const newFile = {
                name: data.data.name,
                file: data.data.file,
                id: data.data.id,
                type: data.data.type,
                size: data.data.size,
                itemName: formatFileName(data.data.name)
              };
              const updatedFiles = [...files, newFile];
              setFiles(updatedFiles);
              editPost({ meta: { digi_files: updatedFiles } });
              if (digicommerceVars.s3_enabled) {
                wp.data.dispatch("core/notices").createNotice(
                  "success",
                  __("File successfully uploaded to Amazon S3", "digicommerce"),
                  { type: "snackbar" }
                );
              } else {
                wp.data.dispatch("core/notices").createNotice(
                  "success",
                  __("File uploaded successfully", "digicommerce"),
                  { type: "snackbar" }
                );
              }
            } else {
              if (data.data?.s3_error) {
                wp.data.dispatch("core/notices").createNotice(
                  "error",
                  digicommerceVars.i18n.s3_upload_failed,
                  { type: "snackbar" }
                );
              } else {
                throw new Error(data.data || "Upload failed");
              }
            }
          } catch (error) {
            console.error("Upload error:", error);
            wp.data.dispatch("core/notices").createNotice(
              "error",
              __("Upload failed. Please try again.", "digicommerce"),
              { type: "snackbar" }
            );
          }
        });
        fileInput.click();
      };
      const updateFile = (index, updatedFile) => {
        const updatedFiles = [...files];
        updatedFiles[index] = updatedFile;
        setFiles(updatedFiles);
        editPost({ meta: { digi_files: updatedFiles } });
      };
      const removeFile = (index) => {
        const fileToRemove = files[index];
        const updatedFiles = files.filter((_, i) => i !== index);
        setFiles(updatedFiles);
        editPost({ meta: { digi_files: updatedFiles } });
        wp.apiFetch({
          path: "/wp/v2/digicommerce/delete-file",
          method: "POST",
          data: {
            file: fileToRemove,
            is_s3: fileToRemove.s3 || false
            // Pass S3 flag to the backend
          }
        }).then((response) => {
          if (response.success) {
            let noticeMessage = response.message;
            if (response.status === "not_found") {
              noticeMessage = digicommerceVars.s3_enabled ? __("File removed from product (was already deleted from S3)", "digicommerce") : __("File removed from product (was already deleted from server)", "digicommerce");
            }
            wp.data.dispatch("core/notices").createNotice(
              "success",
              noticeMessage,
              { type: "snackbar" }
            );
          }
        }).catch((error) => {
          console.error("Error deleting file:", error);
          setFiles([...files]);
          editPost({ meta: { digi_files: [...files] } });
          wp.data.dispatch("core/notices").createNotice(
            "error",
            error.message || __("Failed to delete file. Please try again.", "digicommerce"),
            { type: "snackbar" }
          );
        });
      };
      const handlePriceModeChange = (mode) => {
        setPriceMode(mode);
        editPost({ meta: { digi_price_mode: mode } });
      };
      const addPriceVariation = () => {
        const uniqueId = Date.now().toString() + Math.random().toString(36).substr(2, 5);
        const newVariation = {
          id: uniqueId,
          name: "",
          price: 0,
          salePrice: null,
          files: [],
          subscription_enabled: false,
          subscription_period: "month",
          subscription_free_trial: { duration: 0, period: "days" },
          subscription_signup_fee: 0
        };
        const updatedVariations = [...priceVariations, newVariation];
        setPriceVariations(updatedVariations);
        editPost({ meta: { digi_price_variations: updatedVariations } });
      };
      const updatePriceVariation = (index, updatedVariation) => {
        const updatedVariations = [...priceVariations];
        updatedVariations[index] = updatedVariation;
        setPriceVariations(updatedVariations);
        editPost({ meta: { digi_price_variations: updatedVariations } });
      };
      const removePriceVariation = async (index) => {
        const variationToRemove = priceVariations[index];
        if (variationToRemove.files && variationToRemove.files.length > 0) {
          for (const file of variationToRemove.files) {
            try {
              await wp.apiFetch({
                path: "/wp/v2/digicommerce/delete-file",
                method: "POST",
                data: {
                  file,
                  is_s3: file.s3 || false
                  // Pass S3 flag to the backend
                }
              });
            } catch (error) {
              console.error("Error deleting variation file:", error);
              wp.data.dispatch("core/notices").createNotice(
                "error",
                __("Error deleting some files, but variation was removed", "digicommerce"),
                { type: "snackbar" }
              );
            }
          }
        }
        const updatedVariations = priceVariations.filter((_, i) => i !== index);
        setPriceVariations(updatedVariations);
        editPost({ meta: { digi_price_variations: updatedVariations } });
        wp.data.dispatch("core/notices").createNotice(
          "success",
          digicommerceVars.s3_enabled ? __("Variation and associated S3 files removed successfully", "digicommerce") : __("Variation removed successfully", "digicommerce"),
          { type: "snackbar" }
        );
      };
      const addFeature = () => {
        const newFeature = { name: "", text: "" };
        const updatedFeatures = [...features, newFeature];
        setFeatures(updatedFeatures);
        editPost({ meta: { digi_features: updatedFeatures } });
      };
      const updateFeature = (index, updatedFeature) => {
        const updatedFeatures = [...features];
        updatedFeatures[index] = updatedFeature;
        setFeatures(updatedFeatures);
        editPost({ meta: { digi_features: updatedFeatures } });
      };
      const removeFeature = (index) => {
        const updatedFeatures = features.filter((_, i) => i !== index);
        setFeatures(updatedFeatures);
        editPost({ meta: { digi_features: updatedFeatures } });
      };
      const handleDragStart = (e, index) => {
        e.dataTransfer.setData("text/plain", index);
        e.currentTarget.classList.add("is-dragging");
      };
      const handleDragOver = (e) => {
        e.preventDefault();
        e.currentTarget.classList.add("is-drag-over");
      };
      const handleDragLeave = (e) => {
        e.currentTarget.classList.remove("is-drag-over");
        e.currentTarget.classList.remove("is-dragging");
      };
      const handleDragEnd = (e) => {
        e.currentTarget.classList.remove("is-dragging");
        e.currentTarget.classList.remove("is-drag-over");
        document.querySelectorAll(".digi-file-row, .digi-variation-row, .digi-feature-row").forEach((row) => {
          row.classList.remove("is-drag-over");
          row.classList.remove("is-dragging");
        });
      };
      const handleDrop = (e, dropIndex, items, setItems, metaKey) => {
        e.preventDefault();
        e.currentTarget.classList.remove("is-drag-over");
        e.currentTarget.classList.remove("is-dragging");
        const dragIndex = parseInt(e.dataTransfer.getData("text/plain"));
        if (dragIndex === dropIndex)
          return;
        const updatedItems = [...items];
        const [draggedItem] = updatedItems.splice(dragIndex, 1);
        updatedItems.splice(dropIndex, 0, draggedItem);
        setItems(updatedItems);
        editPost({ meta: { [metaKey]: updatedItems } });
        document.querySelectorAll(".digi-file-row, .digi-variation-row, .digi-feature-row").forEach((row) => {
          row.classList.remove("is-drag-over");
          row.classList.remove("is-dragging");
        });
      };
      const handleFileDrop = (e, dropIndex) => handleDrop(e, dropIndex, files, setFiles, "digi_files");
      const handleVariationDrop = (e, dropIndex) => handleDrop(e, dropIndex, priceVariations, setPriceVariations, "digi_price_variations");
      const handleFeaturesDrop = (e, dropIndex) => handleDrop(e, dropIndex, features, setFeatures, "digi_features");
      return /* @__PURE__ */ React.createElement(React.Fragment, null, /* @__PURE__ */ React.createElement(PluginSidebarMoreMenuItem, { target: "product-details" }, __("Product Details", "digicommerce")), /* @__PURE__ */ React.createElement(
        PluginSidebar,
        {
          name: "product-details",
          title: __("Product Details", "digicommerce"),
          className: "digi-product-sidebar"
        },
        /* @__PURE__ */ React.createElement(PanelBody, { title: __("Pricing", "digicommerce"), initialOpen: true }, /* @__PURE__ */ React.createElement("div", { className: "digi-price-mode-toggle" }, /* @__PURE__ */ React.createElement(ButtonGroup, { className: "digi-price-mode-buttons" }, /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: priceMode === "single" ? "primary" : "secondary",
            onClick: () => handlePriceModeChange("single"),
            className: "digi-price-mode-button"
          },
          __("Single Price", "digicommerce")
        ), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: priceMode === "variations" ? "primary" : "secondary",
            onClick: () => handlePriceModeChange("variations"),
            className: "digi-price-mode-button"
          },
          __("Price Variations", "digicommerce")
        ))), priceMode === "single" ? /* @__PURE__ */ React.createElement("div", { className: "digi-inputs" }, /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Regular Price", "digicommerce"),
            value: price,
            onChange: (value) => {
              if (value === "") {
                setPrice("");
                return;
              }
              const numValue = parseFloat(value);
              if (!isNaN(numValue)) {
                setPrice(numValue);
                editPost({ meta: { digi_price: numValue } });
                if (salePrice && parseFloat(salePrice) >= numValue) {
                  setSalePrice("");
                  editPost({ meta: { digi_sale_price: "" } });
                }
              }
            },
            onBlur: () => {
              const finalValue = parseFloat(price) || 0;
              setPrice(finalValue);
              editPost({ meta: { digi_price: finalValue } });
            },
            type: "number",
            step: "1",
            min: "0",
            inputMode: "decimal",
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          TextControl,
          {
            label: __("Sale Price", "digicommerce"),
            value: salePrice,
            onChange: (value) => {
              if (value === "") {
                setSalePrice("");
                editPost({ meta: { digi_sale_price: "" } });
                return;
              }
              const numValue = parseFloat(value);
              if (!isNaN(numValue)) {
                setSalePrice(numValue);
                editPost({ meta: { digi_sale_price: numValue } });
              }
            },
            onBlur: (e) => {
              const salePriceValue = parseFloat(e.target.value);
              const regularPrice = parseFloat(price);
              if (salePriceValue && regularPrice && salePriceValue >= regularPrice) {
                wp.data.dispatch("core/notices").createNotice(
                  "error",
                  __("Sale price must be less than regular price", "digicommerce"),
                  { type: "snackbar" }
                );
                setSalePrice("");
                editPost({ meta: { digi_sale_price: "" } });
              }
            },
            type: "number",
            step: "1",
            min: "0",
            inputMode: "decimal",
            __nextHasNoMarginBottom: true
          }
        ), /* @__PURE__ */ React.createElement(
          CustomURLField,
          {
            url: wp.url.addQueryArgs(getCheckoutUrl(), { id: postId })
          }
        ), /* @__PURE__ */ React.createElement("div", { className: "digi-slot-container" }, /* @__PURE__ */ React.createElement(Slot, { name: "DigiCommerceSinglePriceAfter" }))) : /* @__PURE__ */ React.createElement("div", { className: "digi-variations-section" }, /* @__PURE__ */ React.createElement("div", { className: "digi-container" }, priceVariations.map((variation, index) => /* @__PURE__ */ React.createElement(
          PriceVariationRow,
          {
            key: index,
            variation,
            index,
            onUpdate: updatePriceVariation,
            onRemove: removePriceVariation,
            onDragStart: handleDragStart,
            onDragOver: handleDragOver,
            onDrop: handleVariationDrop,
            onDragLeave: handleDragLeave,
            onDragEnd: handleDragEnd
          }
        ))), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "primary",
            onClick: addPriceVariation,
            className: "digi-add-button"
          },
          __("Add Price Variation", "digicommerce")
        ))),
        /* @__PURE__ */ React.createElement(PanelBody, { title: __("Downloadable Files", "digicommerce"), initialOpen: false }, files.length > 0 && /* @__PURE__ */ React.createElement(
          "div",
          {
            style: {
              display: "flex",
              backgroundColor: "#f6f7f9",
              borderRadius: "0.75rem",
              fontSize: "0.7rem",
              marginBottom: "1.5rem",
              padding: "1rem",
              alignItems: "center"
            }
          },
          digicommerceVars.s3_enabled ? __("NOTE: When a file is removed, it is completely removed from your S3 bucket.", "digicommerce") : __("NOTE: When a file is removed, it is completely removed from your server.", "digicommerce")
        ), /* @__PURE__ */ React.createElement("div", { className: "digi-container" }, files.map((file, index) => /* @__PURE__ */ React.createElement(
          FileRow,
          {
            key: index,
            file,
            index,
            onUpdate: updateFile,
            onRemove: removeFile,
            onDragStart: handleDragStart,
            onDragOver: handleDragOver,
            onDrop: handleFileDrop,
            onDragLeave: handleDragLeave,
            onDragEnd: handleDragEnd
          }
        ))), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "primary",
            onClick: initFileUpload,
            className: "digi-add-button"
          },
          __("Add New File", "digicommerce")
        )),
        /* @__PURE__ */ React.createElement(PanelBody, { title: __("Description", "digicommerce"), initialOpen: false }, /* @__PURE__ */ React.createElement(
          TextareaControl,
          {
            help: __("Add a detailed description for your product.", "digicommerce"),
            value: productDescription,
            onChange: (value) => {
              setProductDescription(value);
              editPost({ meta: { digi_product_description: value } });
            },
            rows: 4,
            __nextHasNoMarginBottom: true
          }
        )),
        /* @__PURE__ */ React.createElement(PanelBody, { title: __("Gallery", "digicommerce"), initialOpen: false }, /* @__PURE__ */ React.createElement(MediaUploadCheck, null, /* @__PURE__ */ React.createElement(
          MediaUpload,
          {
            onSelect: (media) => {
              const galleryImages = media.map((image) => ({
                id: image.id,
                url: image.sizes?.medium?.url || image.url,
                alt: image.alt || ""
              }));
              editPost({ meta: { digi_gallery: galleryImages } });
            },
            allowedTypes: ["image"],
            multiple: true,
            gallery: true,
            value: postMeta?.digi_gallery?.map((img) => img.id) || [],
            render: ({ open }) => /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "digi-gallery-grid" }, (postMeta?.digi_gallery || []).map((img, index) => /* @__PURE__ */ React.createElement(
              "div",
              {
                key: index,
                className: "digi-gallery-item",
                onClick: open,
                role: "button",
                tabIndex: 0,
                onKeyDown: (e) => {
                  if (e.key === "Enter" || e.key === " ") {
                    open();
                  }
                }
              },
              /* @__PURE__ */ React.createElement("img", { src: img.url, alt: img.alt, className: "digi-gallery-image" }),
              /* @__PURE__ */ React.createElement(
                "button",
                {
                  type: "button",
                  onClick: (e) => {
                    e.stopPropagation();
                    const newGallery = [...postMeta.digi_gallery || []];
                    newGallery.splice(index, 1);
                    editPost({ meta: { digi_gallery: newGallery } });
                  },
                  className: "digi-remove-gallery-image"
                },
                /* @__PURE__ */ React.createElement("span", { className: "sr-only" }, __("Remove image", "digicommerce")),
                /* @__PURE__ */ React.createElement(
                  "svg",
                  {
                    xmlns: "http://www.w3.org/2000/svg",
                    viewBox: "0 0 24 24",
                    width: "20",
                    height: "20",
                    fill: "none",
                    stroke: "currentColor",
                    strokeWidth: "2"
                  },
                  /* @__PURE__ */ React.createElement("path", { d: "M18 6L6 18M6 6l12 12" })
                )
              )
            ))), /* @__PURE__ */ React.createElement(
              Button,
              {
                variant: "primary",
                onClick: open,
                className: "digi-add-button"
              },
              !postMeta?.digi_gallery?.length ? __("Add Gallery Images", "digicommerce") : __("Edit Gallery", "digicommerce")
            ))
          }
        ))),
        /* @__PURE__ */ React.createElement(PanelBody, { title: __("Features", "digicommerce"), initialOpen: false }, /* @__PURE__ */ React.createElement("div", { className: "digi-container" }, features.map((feature, index) => /* @__PURE__ */ React.createElement(
          FeaturesRow,
          {
            key: index,
            feature,
            index,
            onUpdate: updateFeature,
            onRemove: removeFeature,
            onDragStart: handleDragStart,
            onDragOver: handleDragOver,
            onDrop: handleFeaturesDrop,
            onDragLeave: handleDragLeave,
            onDragEnd: handleDragEnd
          }
        ))), /* @__PURE__ */ React.createElement(
          Button,
          {
            variant: "primary",
            onClick: addFeature,
            className: "digi-add-button"
          },
          __("Add Feature", "digicommerce")
        )),
        /* @__PURE__ */ React.createElement(PanelBody, { title: __("Download Instructions", "digicommerce"), initialOpen: false }, /* @__PURE__ */ React.createElement(
          TextareaControl,
          {
            label: __("Instructions for customers", "digicommerce"),
            help: __("These instructions will be shown to customers after purchase", "digicommerce"),
            value: instructions,
            onChange: (value) => {
              setInstructions(value);
              editPost({ meta: { digi_instructions: value } });
            },
            rows: 4,
            __nextHasNoMarginBottom: true
          }
        )),
        /* @__PURE__ */ React.createElement(UpgradePathPanel, null),
        /* @__PURE__ */ React.createElement(ApiDataPanel, null)
      ));
    };
    registerPlugin("digi-product-sidebar", {
      render: ProductSidebar,
      icon: /* @__PURE__ */ React.createElement("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 512 512", width: "24", height: "24", fill: "currentColor", className: "digi__icon" }, /* @__PURE__ */ React.createElement("circle", { cx: "256", cy: "256", r: "256" }), /* @__PURE__ */ React.createElement("path", { d: "M361.4858,348.7728c4.6805,0,8.9099,1.8997,11.9904,4.96,3.1729,3.177,4.952,7.4854,4.9451,11.9755,0,4.672-1.8912,8.9099-4.9451,11.9701-3.1801,3.1788-7.494,4.9621-11.9904,4.9568-4.4924.0071-8.8023-1.7768-11.9755-4.9568-3.1781-3.1723-4.9618-7.4797-4.9568-11.9701,0-4.6805,1.8965-8.9099,4.9568-11.9755,3.1739-3.1794,7.483-4.9641,11.9755-4.96h0ZM199.2159,348.7728c4.6795,0,8.9152,1.8997,11.9755,4.96,3.1815,3.1724,4.9663,7.4826,4.9589,11.9755,0,4.672-1.8933,8.9099-4.9589,11.9701-3.1722,3.1815-7.4827,4.9657-11.9755,4.9568-4.491.0081-8.7996-1.7761-11.9701-4.9568-3.1808-3.1707-4.9656-7.479-4.9589-11.9701,0-4.6805,1.8933-8.9099,4.9589-11.9755,3.1712-3.1801,7.4791-4.9652,11.9701-4.96h0ZM145.0057,129.3637l8.0203,33.6693h-43.2928c-3.9738,0-7.1952,3.2214-7.1952,7.1952s3.2214,7.1952,7.1952,7.1952h100.7712c3.9729,0,7.1936,3.2207,7.1936,7.1936s-3.2207,7.1936-7.1936,7.1936h-50.6219l2.4341,10.2304h-9.0208c-3.9738,0-7.1952,3.2214-7.1952,7.1952s3.2214,7.1952,7.1952,7.1952h64.6784c3.9738.0484,7.1559,3.3091,7.1075,7.2829-.0476,3.9055-3.202,7.0599-7.1075,7.1075h-48.8075l2.528,10.6197h-57.4848c-3.9712,0-7.1904,3.2203-7.1904,7.1936s3.2203,7.1936,7.1904,7.1936h113.7248c3.9738.0481,7.1562,3.3084,7.1082,7.2822-.0472,3.906-3.2022,7.0609-7.1082,7.1082h-49.3802l2.6699,11.2192c-6.3669.7413-12.0949,3.6533-16.4149,7.9669-5.0325,5.0379-8.1557,11.9872-8.1557,19.6373s3.1243,14.6027,8.1557,19.6352c5.0379,5.0411,11.9872,8.1621,19.6437,8.1621h2.5835c-3.7221,1.5774-7.1056,3.8568-9.9659,6.7136-5.8861,5.8685-9.1892,13.8418-9.1776,22.1536,0,8.6475,3.5051,16.4757,9.1776,22.1451,5.6693,5.6693,13.5029,9.1744,22.1451,9.1744,8.6475,0,16.4843-3.5051,22.1536-9.1744,5.6693-5.6693,9.1744-13.4976,9.1744-22.1451.0113-8.3111-3.2904-16.2839-9.1744-22.1536-2.8615-2.8568-6.2461-5.1361-9.9691-6.7136h137.8997c-3.7203,1.5773-7.1018,3.8567-9.9595,6.7136-5.6693,5.6693-9.1776,13.5029-9.1776,22.1536s3.5083,16.4757,9.1776,22.1451c5.6693,5.6693,13.4965,9.1744,22.1451,9.1744s16.4693-3.5051,22.1419-9.1744c5.6725-5.6693,9.1915-13.4976,9.1915-22.1451s-3.52-16.4843-9.1915-22.1536c-2.8512-2.8593-6.2294-5.1392-9.9477-6.7136h10.2677c3.9563,0,7.1851-3.2203,7.1851-7.1968s-3.2288-7.1968-7.1851-7.1968h-199.4944c-3.68,0-7.0304-1.5093-9.4688-3.9381-2.4288-2.4352-3.9445-5.7803-3.9445-9.4656,0-3.68,1.5157-7.0251,3.9445-9.4592,2.4373-2.4288,5.7888-3.9445,9.4688-3.9445h175.072c5.8261,0,11.2224-1.9488,15.5211-5.3291,4.2763-3.3653,7.4464-8.1472,8.8427-13.8368l25.3365-103.9563c.2353-.739.353-1.5104.3488-2.2859,0-3.9733-3.2-7.1968-7.1851-7.1968h-234.5749l-10.0736-42.2912c-.6792-3.3563-3.6295-5.7691-7.0539-5.7685h-30.1205c-3.9735-.0012-7.1956,3.219-7.1968,7.1925v.0043c0,3.9729,3.2207,7.1936,7.1936,7.1936h24.4427v-.0011Z", fill: "#fff" }))
    });
  })();
})();
