(()=>{document.addEventListener("DOMContentLoaded",function(){let c=document.querySelector(".download-pdf");if(!c){console.error("Download button not found.");return}let C=async()=>{try{let b=function(n){let e=digicommercePDF?.i18n?.invoiceId||"Invoice ID",d=digicommercePDF?.i18n?.orderDetails||"Order Details",t=n.querySelector(".pdf-id");if(t){let o=t.textContent.trim().replace(/.*:\s*(.*)$/i,"$1").trim();t.innerHTML="";let g=document.createTextNode(`${e}: ${o}`);t.appendChild(g)}let l=n.querySelector("h2");if(l&&/order\s*details/i.test(l.textContent.trim())){let s=document.createElement("h2");s.className=l.className,s.style.cssText=`
                        ${l.style.cssText||""}
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
                    `,s.innerHTML=`<span style="letter-spacing: normal !important; word-spacing: normal !important;">${d}</span>`,l.parentNode.replaceChild(s,l);let o=document.createElement("style");o.textContent=`
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
                    `,n.appendChild(o)}let i=[],a=document.createTreeWalker(n,NodeFilter.SHOW_TEXT,null,!1),w;for(;w=a.nextNode();)i.push(w);i.forEach(s=>{let o=s.textContent;(o.includes("InvoicelD:")||o.includes("InvoiceID:")||o.includes("Invoice ID:"))&&(o=o.replace(/InvoicelD:|InvoiceID:|Invoice ID:/gi,`${e}:`)),(o.includes("OrderDetails")||o.includes("Order Details"))&&(o=o.replace(/OrderDetails|Order Details/gi,d)),o!==s.textContent&&(s.textContent=o)})};var v=b;let m=document.getElementById("digicommerce-receipt");if(!m)throw new Error(digicommercePDF.i18n.errorMessage);let u=m.querySelector(".pdf-id"),x="invoice";if(u){let e=u.textContent.trim().match(/:\s*(.*?)$/i);e&&e[1]&&(x=e[1].trim().replace("#",""))}let r=window.open("","_blank","width=800,height=600");if(!r){alert("Please allow popups to generate the PDF.");return}r.document.write(`
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
                        ${Array.from(document.styleSheets).map(n=>{try{return Array.from(n.cssRules).map(e=>e.cssText).join(`
`)}catch{return""}}).join(`
`)}
                            
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
            `);let p=m.cloneNode(!0);p.querySelectorAll(".no-invoice, .no-print, .download-pdf").forEach(n=>n.remove()),b(p);let y=p.querySelector(".digicommerce-table");if(y){let n=y.querySelectorAll("thead th");n.length>=2&&(n[0].style.textAlign="left",n[n.length-1].style.textAlign="right");let e=y.querySelectorAll("tbody td");for(let t=0;t<e.length;t++)t%2===0?e[t].style.textAlign="left":e[t].style.textAlign="right";let d=y.querySelectorAll("tfoot th, tfoot td");for(let t=0;t<d.length;t+=2)t<d.length&&(d[t].style.textAlign="right"),t+1<d.length&&(d[t+1].style.textAlign="right")}if(digicommercePDF?.i18n?.allRightsReserved){let n=r.document.createElement("div");n.className="pdf-footer",n.innerHTML=digicommercePDF.i18n.allRightsReserved,n.style.paddingBottom="40px",p.appendChild(n)}r.document.querySelector(".render-container").appendChild(p),r.document.close(),r.onload=async function(){let n=r.document.createElement("style");n.textContent=`
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
                `,r.document.head.appendChild(n);let e=r.document.querySelector(".progress-message");e.innerHTML="";let d=r.document.createTextNode("Capturing image...");e.appendChild(d);try{let t=r.document.querySelector(".render-container > *");b(t),setTimeout(async function(){try{let l=await html2canvas(t,{scale:2,useCORS:!0,allowTaint:!0,backgroundColor:"#ffffff",height:t.offsetHeight+100,letterRendering:!0,logging:!1,removeContainer:!1});e.innerHTML="";let i=r.document.createTextNode("Creating PDF...");e.appendChild(i);let a=new jspdf.jsPDF({orientation:"portrait",unit:"mm",format:"a4"}),w=l.toDataURL("image/png"),s=a.internal.pageSize.getWidth(),o=a.internal.pageSize.getHeight(),g=l.width/l.height,T=s/o,f,h;g>T?(f=s-20,h=f/g):(h=o-20,f=h*g);let D=(s-f)/2,E=(o-h)/2;a.addImage(w,"PNG",D,E,f,h),a.save(`${digicommercePDF.i18n.invoice}-${x}.pdf`),e.innerHTML="";let S=r.document.createTextNode("PDF created successfully! This window will close shortly...");e.appendChild(S),setTimeout(function(){r.close()},1500)}catch(l){console.error("Error creating PDF:",l),e.innerHTML="";let i=r.document.createTextNode("Error creating PDF: "+l.message);e.appendChild(i);let a=document.createElement("button");a.textContent="Close Window",a.style.marginTop="10px",a.style.padding="8px 16px",a.style.backgroundColor="#3b82f6",a.style.color="white",a.style.border="none",a.style.borderRadius="4px",a.style.cursor="pointer",a.onclick=function(){r.close()},e.appendChild(document.createElement("br")),e.appendChild(a)}},1e3)}catch(t){console.error("Error capturing invoice:",t),e.innerHTML="";let l=r.document.createTextNode("Error: "+t.message);e.appendChild(l);let i=document.createElement("button");i.textContent="Close Window",i.style.marginTop="10px",i.style.padding="8px 16px",i.style.backgroundColor="#3b82f6",i.style.color="white",i.style.border="none",i.style.borderRadius="4px",i.style.cursor="pointer",i.onclick=function(){r.close()},e.appendChild(document.createElement("br")),e.appendChild(i)}}}catch(m){console.error("Error generating PDF:",m),alert(digicommercePDF.i18n.errorMessage||"An error occurred while generating the PDF.")}};c.addEventListener("click",async function(v){v.preventDefault();let m=c.querySelector(".text").innerHTML,u=c.querySelector(".icon").innerHTML;c.querySelector(".text").textContent=digicommercePDF.i18n.generating,c.querySelector(".icon").innerHTML='<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="currentColor"><path opacity=".4" d="M0 256C0 114.9 114.1 .5 255.1 0C237.9 .5 224 14.6 224 32c0 17.7 14.3 32 32 32C150 64 64 150 64 256s86 192 192 192c69.7 0 130.7-37.1 164.5-92.6c-3 6.6-3.3 14.8-1 22.2c1.2 3.7 3 7.2 5.4 10.3c1.2 1.5 2.6 3 4.1 4.3c.8 .7 1.6 1.3 2.4 1.9c.4 .3 .8 .6 1.3 .9s.9 .6 1.3 .8c5 2.9 10.6 4.3 16 4.3c11 0 21.8-5.7 27.7-16c-44.3 76.5-127 128-221.7 128C114.6 512 0 397.4 0 256z"/><path d="M224 32c0-17.7 14.3-32 32-32C397.4 0 512 114.6 512 256c0 46.6-12.5 90.4-34.3 128c-8.8 15.3-28.4 20.5-43.7 11.7s-20.5-28.4-11.7-43.7c16.3-28.2 25.7-61 25.7-96c0-106-86-192-192-192c-17.7 0-32-14.3-32-32z"/></svg>',c.disabled=!0;try{await C()}catch(x){console.error("Error:",x),alert(digicommercePDF.i18n.errorMessage)}finally{c.querySelector(".text").innerHTML=m,c.querySelector(".icon").innerHTML=u,c.disabled=!1}})});})();
