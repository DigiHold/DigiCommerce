/**
 * Receipt PDF Generation
 * This script captures the receipt DOM element and converts it to a PDF
 */
document.addEventListener('DOMContentLoaded', function () {
    const downloadButton = document.querySelector('.download-pdf');

    if (!downloadButton) {
        console.error('Download button not found.');
        return;
    }

    /**
     * Generate PDF from the receipt DOM element
     */
    const generatePDF = async () => {
        try {
            // Get the receipt container element
            const receiptContainer = document.getElementById('digicommerce-receipt');
            if (!receiptContainer) {
                throw new Error(digicommercePDF.i18n.errorMessage);
            }

            // Extract the invoice number for the filename
            const pdfNumberElement = receiptContainer.querySelector('.pdf-id');
            let pdfNumber = 'invoice';
            
            if (pdfNumberElement) {
                const fullText = pdfNumberElement.textContent.trim();
                // Extract just the invoice number
                const matches = fullText.match(/:\s*(.*?)$/i);
                if (matches && matches[1]) {
                    pdfNumber = matches[1].trim().replace('#', '');
                }
            }

            // Create a new window for rendering
            const renderWindow = window.open('', '_blank', 'width=800,height=600');
            if (!renderWindow) {
                alert('Please allow popups to generate the PDF.');
                return;
            }

            // Create a basic HTML structure in the new window
            renderWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Generating Invoice PDF...</title>
                    <style>
                        body, html {
                            margin: 0;
                            padding: 0;
                            width: 100%;
                            height: 100%;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            background-color: white;
                            font-family: Arial, Helvetica, sans-serif !important;
                            letter-spacing: normal !important;
                            word-spacing: normal !important;
                        }
                        
                        .render-container {
                            position: relative;
                            max-width: 800px;
                            margin: 20px auto;
                            background-color: white;
                            box-shadow: none;
                            padding: 0;
                        }
                        
                        /* Progress indicator */
                        .progress-message {
                            position: fixed;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            background-color: rgba(0, 0, 0, 0.8);
                            color: white;
                            padding: 20px;
                            border-radius: 8px;
                            text-align: center;
                            z-index: 9999;
                            font-family: Arial, Helvetica, sans-serif !important;
                            letter-spacing: normal !important;
                            word-spacing: normal !important;
                            font-kerning: normal !important;
                            text-rendering: optimizeLegibility !important;
                        }
                        
                        /* Footer styles */
                        .pdf-footer {
                            margin-top: 30px;
                            padding-top: 15px;
                            padding-bottom: 30px;
                            border-top: 1px solid #e5e7eb;
                            font-size: 12px;
                            color: #6b7280;
                            text-align: center;
                            line-height: 1.6;
                            letter-spacing: normal !important;
                            word-spacing: normal !important;
                        }
                        
                        /* Table alignment fixes */
                        .digicommerce-table th:first-child,
                        .digicommerce-table td:first-child {
                            text-align: left !important;
                        }
                        
                        .digicommerce-table th:last-child,
                        .digicommerce-table td:last-child {
                            text-align: right !important;
                        }
                        
                        /* Fix footer spacing in table */
                        .digicommerce-table tfoot th {
                            text-align: right !important;
                            padding-right: 10px !important;
                        }
                        
                        /* FIX: Prevent letter-spacing issues for ALL elements */
                        *, *::before, *::after {
                            letter-spacing: normal !important;
                            word-spacing: normal !important;
                            text-rendering: optimizeLegibility !important;
                            font-kerning: normal !important;
                            white-space: normal !important;
                            font-feature-settings: normal !important;
                            -webkit-font-smoothing: antialiased !important;
                            -moz-osx-font-smoothing: grayscale !important;
                        }
                        
                        /* Specific fixes for elements with known issues */
                        h1, h2, h3, h4, h5, h6, p, span, div, td, th, strong, em, label {
                            letter-spacing: normal !important;
                            word-spacing: normal !important;
                            text-transform: none !important;
                            font-stretch: normal !important;
                        }
                        
                        /* Copy all styles from the original document */
                        ${Array.from(document.styleSheets)
                            .map(styleSheet => {
                                try {
                                    return Array.from(styleSheet.cssRules)
                                        .map(rule => rule.cssText)
                                        .join('\n');
                                } catch (e) {
                                    return '';
                                }
                            })
                            .join('\n')}
                            
                        /* Hide elements that should not be in the invoice */
                        .no-invoice, .no-print, .download-pdf {
                            display: none !important;
                        }
                    </style>
                </head>
                <body>
                    <div class="progress-message" style="font-family: Arial, sans-serif; letter-spacing: normal !important; word-spacing: normal !important; white-space: normal !important; text-rendering: optimizeLegibility !important;">Generating PDF... Please wait.</div>
                    <div class="render-container"></div>
                </body>
                </html>
            `);
            
            // Clone the receipt container and append it to the new window
            const clonedReceipt = receiptContainer.cloneNode(true);
            
            // Remove elements with no-invoice or no-print classes
            const hideElements = clonedReceipt.querySelectorAll('.no-invoice, .no-print, .download-pdf');
            hideElements.forEach(el => el.remove());
            
            // FIX: More robust text replacement that handles both specific issues
            function fixTextContent(element) {
                // Get localized strings from WordPress
                const invoiceIdText = digicommercePDF?.i18n?.invoiceId || 'Invoice ID';
                const orderDetailsText = digicommercePDF?.i18n?.orderDetails || 'Order Details';
                
                // Fix the "InvoicelD:" issue - directly set the content for the pdf-id element
                const invoiceIdElement = element.querySelector('.pdf-id');
                if (invoiceIdElement) {
                    // Get the original text but normalize it
                    const originalText = invoiceIdElement.textContent.trim();
                    // Extract the invoice number
                    const invoiceNumber = originalText.replace(/.*:\s*(.*)$/i, '$1').trim();
                    
                    // Clear and recreate the content with properly formatted text
                    invoiceIdElement.innerHTML = '';
                    const textNode = document.createTextNode(`${invoiceIdText}: ${invoiceNumber}`);
                    invoiceIdElement.appendChild(textNode);
                }
                
                // Fix the "Order Details" issue - directly handle the h2 element
                const orderDetailsElement = element.querySelector('h2');
                if (orderDetailsElement && /order\s*details/i.test(orderDetailsElement.textContent.trim())) {
                    // CRITICAL FIX: Complete element replacement with inline styles
                    const newHeading = document.createElement('h2');
                    newHeading.className = orderDetailsElement.className;
                    
                    // Apply extremely explicit styling to prevent spacing issues
                    newHeading.style.cssText = `
                        ${orderDetailsElement.style.cssText || ''}
                        padding: 16px;
                        margin: 0;
                        letter-spacing: normal !important; 
                        word-spacing: normal !important;
                        white-space: normal !important;
                        text-transform: none !important;
                        font-kerning: normal !important;
                        text-rendering: optimizeLegibility !important;
                        font-feature-settings: normal !important;
                        -webkit-font-smoothing: antialiased !important;
                        -moz-osx-font-smoothing: grayscale !important;
                        font-family: Arial, Helvetica, sans-serif !important;
                    `;
                    
                    // Instead of creating text node, set the HTML directly
                    newHeading.innerHTML = `<span style="letter-spacing: normal !important; word-spacing: normal !important;">${orderDetailsText}</span>`;
                    
                    // Replace the old element
                    orderDetailsElement.parentNode.replaceChild(newHeading, orderDetailsElement);
                    
                    // Add extra safety - also inject a style that targets this specific element
                    const styleForH2 = document.createElement('style');
                    styleForH2.textContent = `
                        h2 {
                            letter-spacing: normal !important;
                            word-spacing: normal !important;
                            white-space: normal !important;
                            text-transform: none !important;
                            font-feature-settings: normal !important;
                        }
                        h2 * {
                            letter-spacing: normal !important;
                            word-spacing: normal !important;
                        }
                    `;
                    element.appendChild(styleForH2);
                }
                
                // Process other text nodes
                const textNodes = [];
                const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null, false);
                let node;
                while (node = walker.nextNode()) {
                    textNodes.push(node);
                }
                
                textNodes.forEach(node => {
                    // Additional text replacements if needed
                    let text = node.textContent;
                    if (text.includes('InvoicelD:') || text.includes('InvoiceID:') || 
                        text.includes('Invoice ID:')) {
                        text = text.replace(/InvoicelD:|InvoiceID:|Invoice ID:/gi, `${invoiceIdText}:`);
                    }
                    if (text.includes('OrderDetails') || text.includes('Order Details')) {
                        text = text.replace(/OrderDetails|Order Details/gi, orderDetailsText);
                    }
                    // Update node text if changed
                    if (text !== node.textContent) {
                        node.textContent = text;
                    }
                });
            }
            
            // Apply the text fix to the cloned receipt
            fixTextContent(clonedReceipt);
            
            // Fix table alignment issues
            const table = clonedReceipt.querySelector('.digicommerce-table');
            if (table) {
                // Ensure first column is left-aligned and last column is right-aligned
                const headerCells = table.querySelectorAll('thead th');
                if (headerCells.length >= 2) {
                    headerCells[0].style.textAlign = 'left';
                    headerCells[headerCells.length - 1].style.textAlign = 'right';
                }
                
                // Fix all data cells
                const dataCells = table.querySelectorAll('tbody td');
                for (let i = 0; i < dataCells.length; i++) {
                    if (i % 2 === 0) { // First column in each row
                        dataCells[i].style.textAlign = 'left';
                    } else { // Last column in each row
                        dataCells[i].style.textAlign = 'right';
                    }
                }
                
                // Fix footer alignment
                const footerCells = table.querySelectorAll('tfoot th, tfoot td');
                for (let i = 0; i < footerCells.length; i += 2) {
                    if (i < footerCells.length) {
                        footerCells[i].style.textAlign = 'right';
                    }
                    if (i + 1 < footerCells.length) {
                        footerCells[i + 1].style.textAlign = 'right';
                    }
                }
            }
            
            // Add footer to the PDF with proper HTML support
            if (digicommercePDF?.i18n?.allRightsReserved) {
                const footerDiv = renderWindow.document.createElement('div');
                footerDiv.className = 'pdf-footer';
                
                // Set footer HTML content directly to preserve HTML formatting
                footerDiv.innerHTML = digicommercePDF.i18n.allRightsReserved;
                
                // Add extra bottom padding to ensure footer is not cut off
                footerDiv.style.paddingBottom = '40px';
                
                clonedReceipt.appendChild(footerDiv);
            }
            
            // Append the cleaned receipt to the render container
            const container = renderWindow.document.querySelector('.render-container');
            container.appendChild(clonedReceipt);
            
            // Wait for the window to load completely
            renderWindow.document.close();
            
            renderWindow.onload = async function() {
                // CRITICAL FIX: Apply comprehensive styling fixes to ALL text elements
                const globalStyle = renderWindow.document.createElement('style');
                globalStyle.textContent = `
                    /* Global fix for letter-spacing issues */
                    *, *::before, *::after {
                        letter-spacing: normal !important;
                        word-spacing: normal !important;
                        text-rendering: optimizeLegibility !important;
                        -webkit-font-smoothing: antialiased !important;
                        -moz-osx-font-smoothing: grayscale !important;
                        font-kerning: normal !important;
                        white-space: normal !important;
                    }
                    
                    /* Specific text elements that commonly have issues */
                    h1, h2, h3, h4, h5, h6,
                    p, span, div, td, th,
                    .pdf-id, .progress-message {
                        letter-spacing: normal !important;
                        word-spacing: normal !important;
                        text-transform: none !important;
                        font-feature-settings: normal !important;
                        white-space: normal !important;
                    }
                    
                    /* Ensure consistent font rendering */
                    body {
                        font-family: Arial, Helvetica, sans-serif !important;
                    }
                    
                    /* Override any problematic CSS transforms */
                    [style*="letter-spacing"] {
                        letter-spacing: normal !important;
                    }
                    
                    /* Force proper rendering for specific text blocks */
                    .progress-message {
                        text-align: center !important;
                        font-size: 16px !important;
                    }
                `;
                renderWindow.document.head.appendChild(globalStyle);

                // Create text using DOM methods instead of direct assignment
                const progressMessage = renderWindow.document.querySelector('.progress-message');
                progressMessage.innerHTML = ''; // Clear existing content
                const progressText = renderWindow.document.createTextNode('Capturing image...');
                progressMessage.appendChild(progressText);
                
                try {
                    // FIX: Another pass of text fixes after DOM is loaded
                    const loadedReceipt = renderWindow.document.querySelector('.render-container > *');
                    fixTextContent(loadedReceipt);
                    
                    // Use setTimeout to ensure everything is rendered
                    setTimeout(async function() {
                        try {
                            // Additional fixes for specific elements
                            
                            // Use html2canvas to capture the receipt
                            const canvas = await html2canvas(loadedReceipt, {
                                scale: 2, // Higher quality rendering
                                useCORS: true,
                                allowTaint: true,
                                backgroundColor: '#ffffff',
                                // Add more height to ensure the footer isn't cut off
                                height: loadedReceipt.offsetHeight + 100,
                                // FIX: Add rendering options to improve text quality
                                letterRendering: true,
                                logging: false,
                                removeContainer: false
                            });
                            
                            // Create text with DOM methods instead of direct assignment
                            progressMessage.innerHTML = '';
                            const createPdfText = renderWindow.document.createTextNode('Creating PDF...');
                            progressMessage.appendChild(createPdfText);
                            
                            // Create PDF using jsPDF
                            const pdf = new jspdf.jsPDF({
                                orientation: 'portrait',
                                unit: 'mm',
                                format: 'a4'
                            });
                            
                            // Calculate proper scaling
                            const imgData = canvas.toDataURL('image/png');
                            const pdfWidth = pdf.internal.pageSize.getWidth();
                            const pdfHeight = pdf.internal.pageSize.getHeight();
                            
                            const canvasRatio = canvas.width / canvas.height;
                            const pageRatio = pdfWidth / pdfHeight;
                            
                            let finalWidth, finalHeight;
                            
                            if (canvasRatio > pageRatio) {
                                // Image is wider compared to PDF page
                                finalWidth = pdfWidth - 20; // 10mm margins
                                finalHeight = finalWidth / canvasRatio;
                            } else {
                                // Image is taller compared to PDF page
                                finalHeight = pdfHeight - 20; // 10mm margins
                                finalWidth = finalHeight * canvasRatio;
                            }
                            
                            // Center the image on the page
                            const x = (pdfWidth - finalWidth) / 2;
                            const y = (pdfHeight - finalHeight) / 2;
                            
                            // Add the image to the PDF
                            pdf.addImage(imgData, 'PNG', x, y, finalWidth, finalHeight);
                            
                            // Save PDF
                            pdf.save(`${digicommercePDF.i18n.invoice}-${pdfNumber}.pdf`);
                            
                            // Close the render window - create text with DOM methods
                            progressMessage.innerHTML = '';
                            const successText = renderWindow.document.createTextNode('PDF created successfully! This window will close shortly...');
                            progressMessage.appendChild(successText);
                            
                            setTimeout(function() {
                                renderWindow.close();
                            }, 1500);
                        } catch (err) {
                            console.error('Error creating PDF:', err);
                            
                            // Create error message with DOM methods
                            progressMessage.innerHTML = '';
                            const errorText = renderWindow.document.createTextNode('Error creating PDF: ' + err.message);
                            progressMessage.appendChild(errorText);
                            
                            // Add close button
                            const closeBtn = document.createElement('button');
                            closeBtn.textContent = 'Close Window';
                            closeBtn.style.marginTop = '10px';
                            closeBtn.style.padding = '8px 16px';
                            closeBtn.style.backgroundColor = '#3b82f6';
                            closeBtn.style.color = 'white';
                            closeBtn.style.border = 'none';
                            closeBtn.style.borderRadius = '4px';
                            closeBtn.style.cursor = 'pointer';
                            
                            closeBtn.onclick = function() {
                                renderWindow.close();
                            };
                            
                            progressMessage.appendChild(document.createElement('br'));
                            progressMessage.appendChild(closeBtn);
                        }
                    }, 1000);
                } catch (error) {
                    console.error('Error capturing invoice:', error);
                    
                    // Create error message with DOM methods
                    progressMessage.innerHTML = '';
                    const errorText = renderWindow.document.createTextNode('Error: ' + error.message);
                    progressMessage.appendChild(errorText);
                    
                    // Add close button on error
                    const closeBtn = document.createElement('button');
                    closeBtn.textContent = 'Close Window';
                    closeBtn.style.marginTop = '10px';
                    closeBtn.style.padding = '8px 16px';
                    closeBtn.style.backgroundColor = '#3b82f6';
                    closeBtn.style.color = 'white';
                    closeBtn.style.border = 'none';
                    closeBtn.style.borderRadius = '4px';
                    closeBtn.style.cursor = 'pointer';
                    
                    closeBtn.onclick = function() {
                        renderWindow.close();
                    };
                    
                    progressMessage.appendChild(document.createElement('br'));
                    progressMessage.appendChild(closeBtn);
                }
            };
        } catch (error) {
            console.error('Error generating PDF:', error);
            alert(digicommercePDF.i18n.errorMessage || 'An error occurred while generating the PDF.');
        }
    };

    // Attach click event to the download button
    downloadButton.addEventListener('click', async function (e) {
        e.preventDefault();
        const originalText = downloadButton.querySelector('.text').innerHTML;
        const originalIcon = downloadButton.querySelector('.icon').innerHTML;

        downloadButton.querySelector('.text').textContent = digicommercePDF.i18n.generating;
        downloadButton.querySelector('.icon').innerHTML = `<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="currentColor"><path opacity=".4" d="M0 256C0 114.9 114.1 .5 255.1 0C237.9 .5 224 14.6 224 32c0 17.7 14.3 32 32 32C150 64 64 150 64 256s86 192 192 192c69.7 0 130.7-37.1 164.5-92.6c-3 6.6-3.3 14.8-1 22.2c1.2 3.7 3 7.2 5.4 10.3c1.2 1.5 2.6 3 4.1 4.3c.8 .7 1.6 1.3 2.4 1.9c.4 .3 .8 .6 1.3 .9s.9 .6 1.3 .8c5 2.9 10.6 4.3 16 4.3c11 0 21.8-5.7 27.7-16c-44.3 76.5-127 128-221.7 128C114.6 512 0 397.4 0 256z"/><path d="M224 32c0-17.7 14.3-32 32-32C397.4 0 512 114.6 512 256c0 46.6-12.5 90.4-34.3 128c-8.8 15.3-28.4 20.5-43.7 11.7s-20.5-28.4-11.7-43.7c16.3-28.2 25.7-61 25.7-96c0-106-86-192-192-192c-17.7 0-32-14.3-32-32z"/></svg>`;
        downloadButton.disabled = true;

        try {
            await generatePDF();
        } catch (error) {
            console.error('Error:', error);
            alert(digicommercePDF.i18n.errorMessage);
        } finally {
            downloadButton.querySelector('.text').innerHTML = originalText;
            downloadButton.querySelector('.icon').innerHTML = originalIcon;
            downloadButton.disabled = false;
        }
    });
});