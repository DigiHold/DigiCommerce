(() => {
  // resources/js/front/pdf-generator.js
  document.addEventListener("DOMContentLoaded", function() {
    const downloadButton = document.querySelector(".download-pdf");
    if (!downloadButton) {
      console.error("Download button not found.");
      return;
    }
    const convertImageToBase64 = (url) => {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.onload = () => {
          const canvas = document.createElement("canvas");
          canvas.width = img.width;
          canvas.height = img.height;
          const ctx = canvas.getContext("2d");
          ctx.drawImage(img, 0, 0);
          resolve(canvas.toDataURL("image/png"));
        };
        img.onerror = () => reject(new Error(`Failed to load image: ${url}`));
        img.src = url;
      });
    };
    const extractDataFromDOM = async () => {
      const receiptContainer = document.getElementById("digicommerce-receipt");
      if (!receiptContainer) {
        throw new Error(digicommercePDF.i18n.errorMessage);
      }
      const pdfNumber = receiptContainer.querySelector(".pdf-id")?.textContent.split(":")[1]?.trim() || digicommercePDF.i18n.unknown;
      const pdfDate = receiptContainer.querySelector(".pdf-date")?.textContent.split("Date:")[1]?.trim() || digicommercePDF.i18n.unknown;
      const pdfNextDate = receiptContainer.querySelector(".pdf-next-date")?.textContent.split("Next Payment:")[1]?.trim() || "";
      const businessInfo = receiptContainer.querySelector(".business-info");
      const businessName = businessInfo.querySelector(".business-name")?.textContent.trim() || "";
      const businessAddresses = Array.from(businessInfo.querySelectorAll(".business-address span")).map((span) => span.textContent.trim()).filter(Boolean);
      const billingInfo = receiptContainer.querySelector(".billing-info");
      const billingCompany = billingInfo.querySelector(".billing-company")?.textContent.trim() || "";
      const billingAddresses = Array.from(billingInfo.querySelectorAll(".billing-address span")).map((span) => span.textContent.trim()).filter(Boolean);
      const logoElement = document.getElementById("digicommerce-receipt-logo");
      const logoUrl = logoElement ? logoElement.src : null;
      const logoBase64 = logoUrl ? await convertImageToBase64(logoUrl) : null;
      const items = Array.from(receiptContainer.querySelectorAll(".digicommerce-table:not(.no-invoice) tbody tr")).map((row) => {
        const productElement = row.querySelector("td:first-child");
        let productText = "";
        if (productElement) {
          const clonedElement = productElement.cloneNode(true);
          const noInvoice = clonedElement.querySelectorAll(".no-invoice");
          noInvoice.forEach((el) => el.remove());
          productText = clonedElement.textContent.trim();
        }
        const total = row.querySelector("td:last-child")?.textContent.trim() || digicommercePDF.i18n.unknown;
        return {
          product: productText || digicommercePDF.i18n.unknown,
          total
        };
      });
      const totals = Array.from(receiptContainer.querySelectorAll(".digicommerce-table tfoot tr")).map((row) => ({
        label: row.querySelector("th")?.textContent.trim() || "",
        value: row.querySelector("td")?.textContent.trim() || ""
      }));
      return {
        pdfNumber,
        pdfDate,
        pdfNextDate,
        businessName,
        businessAddresses,
        billingCompany,
        billingAddresses,
        logoBase64,
        items,
        totals
      };
    };
    const generatePDF = async () => {
      try {
        let parseHTMLToPlainText2 = function(html) {
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = html;
          tempDiv.querySelectorAll("strong").forEach((el) => {
            const boldText = document.createTextNode(el.textContent);
            el.replaceWith(boldText);
          });
          return tempDiv.textContent.trim();
        };
        var parseHTMLToPlainText = parseHTMLToPlainText2;
        const {
          pdfNumber,
          pdfDate,
          pdfNextDate,
          businessName,
          businessAddresses,
          billingCompany,
          billingAddresses,
          logoBase64,
          items,
          totals
        } = await extractDataFromDOM();
        const sanitizedpdfNumber = pdfNumber.replace("#", "");
        const docDefinition = {
          content: [
            {
              columns: [
                logoBase64 ? { image: logoBase64, width: 80 } : { text: "" },
                {
                  stack: [
                    { text: `${digicommercePDF.i18n.invoiceId}: ${sanitizedpdfNumber}`, style: "invoiceId" },
                    { text: `${digicommercePDF.i18n.date}: ${pdfDate}`, style: "invoiceDate" },
                    pdfNextDate ? { text: `${digicommercePDF.i18n.nextDate}: ${pdfNextDate}`, style: "invoiceDate" } : null
                  ],
                  alignment: "right"
                }
              ],
              margin: [0, 0, 0, 20]
            },
            {
              columns: [
                {
                  stack: [
                    { text: digicommercePDF.i18n.from, style: "subheader" },
                    { text: businessName, style: "businessName" },
                    ...businessAddresses.map((line) => ({ text: line }))
                  ]
                },
                {
                  stack: [
                    { text: digicommercePDF.i18n.billTo, style: "subheader" },
                    billingCompany ? { text: billingCompany, style: "billingName" } : null,
                    ...billingAddresses.map((line) => ({ text: line }))
                  ].filter(Boolean),
                  alignment: "right"
                }
              ],
              columnGap: 20,
              margin: [0, 0, 0, 20]
            },
            {
              text: digicommercePDF.i18n.orderDetails,
              style: "sectionHeader",
              margin: [0, 10, 0, 10]
            },
            {
              table: {
                headerRows: 1,
                widths: ["*", "auto"],
                body: [
                  [
                    { text: digicommercePDF.i18n.product, style: "tableHeader" },
                    { text: digicommercePDF.i18n.total, style: "tableHeader", alignment: "right" }
                  ],
                  ...items.map((item) => [
                    { text: item.product, style: "tableData" },
                    { text: item.total, style: "tableData", alignment: "right" }
                  ]),
                  ...totals.map((total) => [
                    {
                      text: total.label,
                      style: total.label.includes("Subtotal") || total.label.includes("VAT") ? "tableFooterSmall" : "tableFooterBold"
                    },
                    {
                      text: total.value,
                      alignment: "right",
                      style: total.label.includes("Subtotal") || total.label.includes("VAT") ? "tableFooterSmall" : total.label.includes("Total") ? "tableFooterGreen" : "tableFooterBold"
                    }
                  ])
                ]
              },
              layout: "lightHorizontalLines"
            }
          ],
          footer: {
            columns: [
              {
                stack: [
                  {
                    text: businessName + "\n",
                    style: "footerBusiness"
                  },
                  {
                    text: parseHTMLToPlainText2(digicommercePDF.i18n.allRightsReserved),
                    style: "footerText"
                  }
                ],
                alignment: "center",
                width: "*"
              }
            ],
            margin: [40, 20]
          },
          styles: {
            // Header Styles
            invoiceId: { fontSize: 18, bold: true, color: "#09053A", margin: [0, 0, 0, 5] },
            // Invoice ID in bold with main color
            invoiceDate: { fontSize: 12, color: "#656071" },
            // Smaller font size for date
            // Section Headers
            subheader: { fontSize: 16, bold: true, color: "#09053A", margin: [0, 5, 0, 5] },
            // Header for sections like "From" and "Bill To"
            sectionHeader: { fontSize: 15, bold: true, color: "#09053A" },
            // Header for "Order Details"
            // Table Styles
            tableHeader: { fontSize: 16, bold: true, color: "#09053A" },
            // Table column headers
            tableData: { fontSize: 11 },
            // Standard table row data
            tableFooter: { fontSize: 11, bold: false },
            // General footer data
            tableFooterSmall: { fontSize: 11, bold: true, color: "#09053A" },
            // Subtotal and VAT rows, smaller and lighter
            tableFooterBold: { fontSize: 16, bold: true },
            // Bold styling for labels
            tableFooterGreen: { fontSize: 16, bold: true, color: "#16a34a" },
            // Highlight Total Price with green color
            // Business and Billing Styles
            businessName: { fontSize: 16, bold: true, color: "#09053A" },
            // Business name bold and prominent
            billingName: { fontSize: 16, bold: true, color: "#09053A" },
            // Billing company bold and prominent
            // Footer
            footerBusiness: { fontSize: 10, bold: true, color: "#09053A" },
            footerText: { fontSize: 8, color: "#656071" }
          },
          pageMargins: [40, 40, 40, 80],
          defaultStyle: {
            fontSize: 11,
            lineHeight: 1.4
          }
        };
        pdfMake.createPdf(docDefinition).download(`${digicommercePDF.i18n.invoice}-${sanitizedpdfNumber}.pdf`);
      } catch (error) {
        console.error("Error generating PDF:", error);
        alert(digicommercePDF.i18n.errorMessage || "An error occurred while generating the PDF.");
      }
    };
    downloadButton.addEventListener("click", async function(e) {
      e.preventDefault();
      const originalText = downloadButton.querySelector(".text").innerHTML;
      const originalIcon = downloadButton.querySelector(".icon").innerHTML;
      downloadButton.querySelector(".text").textContent = digicommercePDF.i18n.generating;
      downloadButton.querySelector(".icon").innerHTML = `<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="currentColor"><path opacity=".4" d="M0 256C0 114.9 114.1 .5 255.1 0C237.9 .5 224 14.6 224 32c0 17.7 14.3 32 32 32C150 64 64 150 64 256s86 192 192 192c69.7 0 130.7-37.1 164.5-92.6c-3 6.6-3.3 14.8-1 22.2c1.2 3.7 3 7.2 5.4 10.3c1.2 1.5 2.6 3 4.1 4.3c.8 .7 1.6 1.3 2.4 1.9c.4 .3 .8 .6 1.3 .9s.9 .6 1.3 .8c5 2.9 10.6 4.3 16 4.3c11 0 21.8-5.7 27.7-16c-44.3 76.5-127 128-221.7 128C114.6 512 0 397.4 0 256z"/><path d="M224 32c0-17.7 14.3-32 32-32C397.4 0 512 114.6 512 256c0 46.6-12.5 90.4-34.3 128c-8.8 15.3-28.4 20.5-43.7 11.7s-20.5-28.4-11.7-43.7c16.3-28.2 25.7-61 25.7-96c0-106-86-192-192-192c-17.7 0-32-14.3-32-32z"/></svg>`;
      downloadButton.disabled = true;
      try {
        await generatePDF();
      } catch (error) {
        console.error("Error:", error);
        alert(digicommercePDF.i18n.errorMessage);
      } finally {
        downloadButton.querySelector(".text").innerHTML = originalText;
        downloadButton.querySelector(".icon").innerHTML = originalIcon;
        downloadButton.disabled = false;
      }
    });
  });
})();
