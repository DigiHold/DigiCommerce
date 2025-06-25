(()=>{(function(){"use strict";document.readyState==="loading"?document.addEventListener("DOMContentLoaded",v):v();function v(){$(),E(),w(),z(),j(),R(),G(),X(),K()}function $(){let t=document.querySelectorAll('input[name="digi_price_mode"]'),e=document.querySelector(".pricing-single"),n=document.querySelector(".pricing-variations");!t.length||!e||!n||t.forEach(i=>{i.addEventListener("change",function(){this.value==="single"?(e.style.display="block",n.style.display="none"):(e.style.display="none",n.style.display="block")})})}function E(){let t=document.querySelector(".add-variation"),e=document.querySelector(".variations-list"),n=document.getElementById("variation-template");!t||!e||!n||(t.addEventListener("click",function(){let r=e.querySelectorAll(".variation-item").length,o=n.innerHTML;o=o.replace(/\{\{INDEX\}\}/g,r),o=o.replace(/\{\{NUMBER\}\}/g,r+1),e.insertAdjacentHTML("beforeend",o),y(),x()}),document.addEventListener("click",function(i){i.target.classList.contains("remove-variation")&&confirm(digicommerceVars.i18n.removeConfirm)&&(i.target.closest(".variation-item").remove(),y())}),document.addEventListener("input",function(i){(i.target.classList.contains("variation-file-name")||i.target.classList.contains("variation-file-item-name")||i.target.classList.contains("version-number")||i.target.classList.contains("version-changelog"))&&g()}),document.addEventListener("change",function(i){i.target.type==="checkbox"&&i.target.name&&i.target.name.includes("[isDefault]")&&i.target.checked&&document.querySelectorAll('input[name*="[isDefault]"]').forEach(o=>{o!==i.target&&(o.checked=!1)})}),document.addEventListener("click",function(i){i.target.classList.contains("add-variation-file-btn")&&M(i),i.target.classList.contains("remove-variation-file-btn")&&k(i)}))}function y(){document.querySelectorAll(".variation-item").forEach((e,n)=>{e.dataset.index=n;let i=e.querySelector(".variation-number");i&&(i.textContent=n+1),e.querySelectorAll("input, select, textarea").forEach(a=>{a.name&&(a.name=a.name.replace(/\[\d+\]/,`[${n}]`))});let o=e.querySelector(".add-variation-file-btn");o&&(o.dataset.variationIndex=n)})}class I{constructor(){this.isUploading=!1}async uploadFile(e,n,i){let r=e.files[0];if(!r)return;let o=100*1024*1024;if(r.size>o){i&&i(digicommerceVars.i18n.file_too_large);return}let a=["pdf","doc","docx","xls","xlsx","txt","zip","rar","7z","jpg","jpeg","png","gif","svg","mp4","mp3","wav"],c=r.name.split(".").pop().toLowerCase();if(!a.includes(c)){i&&i(digicommerceVars.i18n.invalid_file);return}this.isUploading=!0;try{let s=new FormData;s.append("file",r),s.append("action","digicommerce_upload_file"),s.append("upload_nonce",digicommerceVars.upload_nonce);let p=await(await fetch(digicommerceVars.ajaxurl,{method:"POST",body:s})).json();if(p.success){let Z={id:p.data.id,name:this.formatFileName(r.name),file:p.data.file,type:r.type,size:r.size,itemName:this.formatFileName(r.name),s3:digicommerceVars.s3_enabled,versions:[]};n&&n(Z)}else i&&i(p.data||digicommerceVars.i18n.upload_failed)}catch(s){console.error("Upload error:",s),i&&i(digicommerceVars.i18n.upload_failed)}finally{this.isUploading=!1}}async deleteFile(e,n,i){try{let r=await wp.apiFetch({path:"/wp/v2/digicommerce/delete-file",method:"POST",data:{file:e,is_s3:digicommerceVars.s3_enabled}});if(r.success){let o=r.message;r.status==="not_found"?o=digicommerceVars.s3_enabled?digicommerceVars.i18n.file_removed_s3:digicommerceVars.i18n.file_removed_server:digicommerceVars.s3_enabled&&(o=digicommerceVars.i18n.file_deleted_s3),n&&n(r)}else i&&i(r.message||digicommerceVars.i18n.delete_failed)}catch(r){console.error("Delete error:",r);let o=r.message||digicommerceVars.i18n.delete_failed;digicommerceVars.s3_enabled&&r.message&&r.message.includes("S3")&&(o=digicommerceVars.i18n.s3_delete_failed),i&&i(o)}}formatFileName(e){return e.replace(/\.[^/.]+$/,"").replace(/-/g," ")}formatFileSize(e){if(e===0)return"0 Bytes";let n=1024,i=["Bytes","KB","MB","GB"],r=Math.floor(Math.log(e)/Math.log(n));return parseFloat((e/Math.pow(n,r)).toFixed(2))+" "+i[r]}}let u=new I;function w(){let t=document.querySelector(".upload-file-btn");t&&t.addEventListener("click",F),document.addEventListener("click",function(e){e.target.classList.contains("remove-file-btn")&&C(e)}),document.addEventListener("click",function(e){e.target.classList.contains("add-version-btn")&&U(e),e.target.classList.contains("remove-version-btn")&&P(e),e.target.classList.contains("upload-version-btn")&&_(e)}),document.addEventListener("input",function(e){(e.target.classList.contains("file-name-input")||e.target.classList.contains("file-item-name-input")||e.target.classList.contains("version-number"))&&m()})}function F(){let t=document.createElement("input");t.type="file",t.accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav",t.addEventListener("change",function(){this.files[0]&&(f(digicommerceVars.s3_enabled?digicommerceVars.i18n.s3_uploading:digicommerceVars.i18n.uploading),u.uploadFile(this,function(e){T(e),m(),d(),l(digicommerceVars.i18n.saved,"success")},function(e){d(),l(e,"error")}))}),t.click()}function M(t){let e=t.target.dataset.variationIndex,n=document.createElement("input");n.type="file",n.accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav",n.addEventListener("change",function(){this.files[0]&&(f(digicommerceVars.s3_enabled?digicommerceVars.i18n.s3_uploading:digicommerceVars.i18n.uploading),u.uploadFile(this,function(i){h(e,i),d(),l(digicommerceVars.i18n.saved,"success")},function(i){d(),l(i,"error")}))}),n.click()}function _(t){let e=t.target.closest(".version-item"),n=t.target.closest(".file-item, .variation-file-item"),i=document.createElement("input");i.type="file",i.accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav",i.addEventListener("change",function(){this.files[0]&&(f(digicommerceVars.s3_enabled?digicommerceVars.i18n.s3_uploading:digicommerceVars.i18n.uploading),u.uploadFile(this,function(r){let o=e.querySelector(".version-file");o.value=r.file,m(),d(),l(digicommerceVars.i18n.saved,"success")},function(r){d(),l(r,"error")}))}),i.click()}function C(t){if(!confirm(digicommerceVars.i18n.removeConfirm))return;let e=t.target.closest(".file-item"),n=b(e);f(digicommerceVars.i18n.deleting),u.deleteFile(n,function(i){e.remove(),m(),d(),l(i.message||digicommerceVars.i18n.remove,"success")},function(i){d(),l(i,"error")})}function k(t){if(!confirm(digicommerceVars.i18n.removeConfirm))return;let e=t.target.closest(".variation-file-item"),n=V(e);f(digicommerceVars.i18n.deleting),u.deleteFile(n,function(i){e.remove(),g(),d(),l(i.message||digicommerceVars.i18n.remove,"success")},function(i){d(),l(i,"error")})}function T(t){let e=document.querySelector(".files-container");if(!e)return;let n=e.querySelector("p");n&&n.textContent.includes("No files")&&n.remove();let i=e.children.length,r=H(t,i);e.insertAdjacentHTML("beforeend",r)}function h(t,e){let n=document.querySelector(`.variation-item[data-index="${t}"]`);if(!n)return;let i=n.querySelector(".variation-files-container");if(!i){let c=`
				<div class="variation-files-section">
					<h5>${digicommerceVars.i18n.downloadFiles}</h5>
					<div class="variation-files-container"></div>
					<button type="button" class="button add-variation-file-btn" data-variation-index="${t}">
						${digicommerceVars.i18n.addDownloadFile}
					</button>
				</div>
			`;return n.querySelector(".variation-basic-fields").insertAdjacentHTML("afterend",c),h(t,e)}let r=i.querySelector(".no-variation-files");r&&r.remove();let o=i.children.length,a=A(e,o);i.insertAdjacentHTML("beforeend",a),g()}function g(){document.querySelectorAll(".variation-item").forEach(function(e,n){let i=e.querySelector(".variation-files-container"),r=[];i&&i.querySelectorAll(".variation-file-item").forEach(function(c){let s=V(c);s.file&&(digicommerceVars.license_enabled&&(s.versions=N(c)),r.push(s))});let o=e.querySelector(".variation-files-data");o?o.name=`variations[${n}][files]`:(o=document.createElement("input"),o.type="hidden",o.className="variation-files-data",o.name=`variations[${n}][files]`,e.appendChild(o)),o.value=JSON.stringify(r)})}function N(t){let e=[];return t.querySelectorAll(".version-item").forEach(function(i){let r=i.querySelector(".version-number").value,o=i.querySelector(".version-changelog")?.value||"";r.trim()&&/^\d+\.\d+\.\d+$/.test(r.trim())&&e.push({version:r.trim(),changelog:o.trim(),release_date:new Date().toISOString()})}),e}function H(t,e){return`
			<div class="file-item" data-index="${e}">
				<div class="file-header">
					<h4>${digicommerceVars.i18n.downloadFiles} #${e+1}</h4>
					<button type="button" class="button-link-delete remove-file-btn">
						${digicommerceVars.i18n.remove}
					</button>
				</div>
				
				<div class="file-details">
					<div class="field-group">
						<label>${digicommerceVars.i18n.fileName}</label>
						<input type="text" class="file-name-input" value="${t.name||""}" placeholder="${digicommerceVars.i18n.fileName}" />
					</div>
					
					<div class="field-group">
						<label>${digicommerceVars.i18n.itemName}</label>
						<input type="text" class="file-item-name-input" value="${t.itemName||""}" placeholder="${digicommerceVars.i18n.itemName}" />
					</div>
					
					<div class="field-group">
						<label>${digicommerceVars.i18n.filePath}</label>
						<input type="text" class="file-path-input" value="${t.file||""}" readonly />
					</div>
					
					${t.size?`
					<div class="field-group">
						<label>${digicommerceVars.i18n.fileSize}</label>
						<span class="file-size">${u.formatFileSize(t.size)}</span>
					</div>
					`:""}
				</div>
				
				${digicommerceVars.license_enabled?`
				<div class="file-versions">
					<h5>${digicommerceVars.i18n.versions}</h5>
					<div class="versions-container">
						<p class="no-versions">${digicommerceVars.i18n.noVersionsAdded}</p>
					</div>
					<button type="button" class="button add-version-btn" data-file-index="${e}">
						${digicommerceVars.i18n.addVersion}
					</button>
				</div>
				`:""}
				
				<!-- Hidden data -->
				<input type="hidden" class="file-id" value="${t.id||""}" />
				<input type="hidden" class="file-type" value="${t.type||""}" />
			</div>
		`}function A(t,e){return`
			<div class="variation-file-item" data-file-index="${e}">
				<div class="variation-file-header">
					<span>${t.name||digicommerceVars.i18n.unnamedFile}</span>
					<button type="button" class="button-link-delete remove-variation-file-btn">
						${digicommerceVars.i18n.remove}
					</button>
				</div>
				<div class="variation-file-details">
					<p>
						<label>${digicommerceVars.i18n.fileName}</label>
						<input type="text" class="variation-file-name" value="${t.name||""}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.itemName}</label>
						<input type="text" class="variation-file-item-name" value="${t.itemName||""}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.filePath}</label>
						<input type="text" class="variation-file-path" value="${t.file||""}" readonly />
					</p>
					
					${digicommerceVars.license_enabled?`
					<div class="variation-file-versions">
						<h5>${digicommerceVars.i18n.versions}</h5>
						<div class="versions-container">
							<p class="no-versions">${digicommerceVars.i18n.noVersionsAdded}</p>
						</div>
						<button type="button" class="button add-version-btn" data-file-type="variation" data-file-index="${e}">
							${digicommerceVars.i18n.addVersion}
						</button>
					</div>
					`:""}
					
					<!-- Hidden fields -->
					<input type="hidden" class="variation-file-id" value="${t.id||""}" />
					<input type="hidden" class="variation-file-type" value="${t.type||""}" />
				</div>
			</div>
		`}function b(t){return{id:t.querySelector(".file-id").value,name:t.querySelector(".file-name-input").value,file:t.querySelector(".file-path-input").value,type:t.querySelector(".file-type").value,itemName:t.querySelector(".file-item-name-input").value}}function V(t){return{id:t.querySelector(".variation-file-id").value,name:t.querySelector(".variation-file-name").value,file:t.querySelector(".variation-file-path").value,type:t.querySelector(".variation-file-type").value,itemName:t.querySelector(".variation-file-item-name").value}}function m(){let t=document.querySelector(".files-list"),e=document.querySelector("#digi_files");if(!t||!e)return;let n=[];t.querySelectorAll(".file-item").forEach(function(r){let o=b(r);digicommerceVars.license_enabled&&(o.versions=D(r)),n.push(o)}),e.value=JSON.stringify(n)}function D(t){let e=[];return t.querySelectorAll(".version-item").forEach(function(i){let r=i.querySelector(".version-number").value,o=i.querySelector(".version-changelog").value;r.trim()&&/^\d+\.\d+\.\d+$/.test(r.trim())&&e.push({version:r.trim(),changelog:o.trim(),release_date:new Date().toISOString()})}),e}function U(t){let n=t.target.closest(".file-item, .variation-file-item").querySelector(".versions-container, .variation-file-versions .versions-container"),i=n.querySelector(".no-versions");i&&i.remove();let r=n.children.length,o=B(r);n.insertAdjacentHTML("beforeend",o),n.lastElementChild.querySelector(".version-number").addEventListener("blur",function(){let s=/^\d+\.\d+\.\d+$/;this.value&&!s.test(this.value.trim())&&(l(digicommerceVars.i18n.semanticVersioning,"error"),this.focus())}),m()}function P(t){if(!confirm(digicommerceVars.i18n.removeConfirm))return;t.target.closest(".version-item").remove(),m()}function B(t){return`
			<div class="version-item" data-version-index="${t}">
				<div class="version-header">
					<span class="version-label">${digicommerceVars.i18n.versions} ${t+1}</span>
					<button type="button" class="button-link-delete remove-version-btn">
						${digicommerceVars.i18n.remove}
					</button>
				</div>
				<div class="version-fields">
					<p>
						<label>${digicommerceVars.i18n.versionNumber}</label>
						<input type="text" class="version-number" value="" placeholder="${digicommerceVars.i18n.versionPlaceholder}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.changelog}</label>
						<textarea class="version-changelog" rows="3" placeholder="${digicommerceVars.i18n.changelogPlaceholder}"></textarea>
					</p>
				</div>
			</div>
		`}function z(){let t=document.querySelector(".add-feature");t&&(t.addEventListener("click",function(){let e=document.querySelector(".features-list"),n=e.children.length,i=`
                <div class="feature-item">
                    <p>
                        <label>${digicommerceVars.i18n.featureName}</label>
                        <input type="text" name="features[${n}][name]" value="" />
                    </p>
                    <p>
                        <label>${digicommerceVars.i18n.featureDescription}</label>
                        <input type="text" name="features[${n}][text]" value="" />
                    </p>
                    <p>
                        <button type="button" class="button-link-delete remove-feature">${digicommerceVars.i18n.remove}</button>
                    </p>
                </div>
            `;e.insertAdjacentHTML("beforeend",i)}),document.addEventListener("click",function(e){e.target.classList.contains("remove-feature")&&confirm(digicommerceVars.i18n.removeConfirm)&&e.target.closest(".feature-item").remove()}))}function j(){let t=document.querySelector(".select-gallery");t&&t.addEventListener("click",function(){Y("gallery",function(e){O(e)})})}function O(t){let e=document.querySelector(".gallery-preview"),n=document.querySelector("#digi_gallery"),i=t.map(o=>({id:o.id,url:o.sizes&&o.sizes.thumbnail?o.sizes.thumbnail.url:o.url,alt:o.alt||""}));n.value=JSON.stringify(i);let r='<div class="gallery-images">';i.forEach(o=>{r+=`
                <div class="gallery-image">
                    <img src="${o.url}" alt="${o.alt}" style="max-width: 100px; height: auto;" />
                </div>
            `}),r+="</div>",e.innerHTML=r}function R(){let t=document.querySelector(".add-bundle-product"),e=document.querySelector(".bundle-products-list");!t||!e||(t.addEventListener("click",function(){let n=e.children.length,i=`<option value="">${digicommerceVars.i18n.selectProduct}</option>`;digicommerceVars.available_products&&digicommerceVars.available_products.length>0&&digicommerceVars.available_products.forEach(function(a){i+=`<option value="${a.id}">${J(a.title)}</option>`});let r=`
				<div class="bundle-product-item">
					<p>
						<label>${digicommerceVars.i18n.product}</label>
						<select name="bundle_products[${n}]">
							${i}
						</select>
					</p>
					<p>
						<button type="button" class="button-link-delete remove-bundle-product">${digicommerceVars.i18n.remove}</button>
					</p>
				</div>
			`;e.insertAdjacentHTML("beforeend",r);let o=e.querySelector("p");o&&o.textContent.includes("No products selected yet")&&o.remove()}),document.addEventListener("click",function(n){n.target.classList.contains("remove-bundle-product")&&confirm(digicommerceVars.i18n.removeConfirm)&&(n.target.closest(".bundle-product-item").remove(),document.querySelectorAll(".bundle-product-item").length===0&&(e.innerHTML="<p>"+digicommerceVars.i18n.noProductsSelected+"</p>"))}))}function J(t){let e={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"};return t.replace(/[&<>"']/g,function(n){return e[n]})}function G(){let t=document.querySelector(".add-upgrade-path"),e=document.querySelector(".upgrade-paths-list"),n=document.getElementById("upgrade-path-template");!t||!e||!n||(t.addEventListener("click",function(){let r=e.querySelectorAll(".upgrade-path-item").length,o=r+1,a=n.innerHTML;a=a.replace(/\{\{INDEX\}\}/g,r),a=a.replace(/\{\{NUMBER\}\}/g,o);let c=document.createElement("div");c.innerHTML=a;let s=c.firstElementChild;e.appendChild(s),S(s,!0)}),document.querySelectorAll(".upgrade-path-item").forEach(i=>{S(i,!1)}))}function S(t,e=!1){if(t.dataset.handlersAttached==="true")return;let n=t.querySelector(".remove-upgrade-path");n&&n.addEventListener("click",function(){confirm(digicommerceVars.i18n.removeConfirm)&&(t.remove(),W())});let i=t.querySelector(".include-coupon-checkbox"),r=t.querySelector(".coupon-options");i&&r&&i.addEventListener("change",function(){r.style.display=this.checked?"block":"none"});let o=t.querySelector(".target-product-select"),a=t.querySelector(".target-variation-select");o&&a&&(o.addEventListener("change",function(){L(this,a)}),e&&o.value&&L(o,a)),t.dataset.handlersAttached="true"}function L(t,e){let n=t.value;if(e.innerHTML=`<option value="">${digicommerceVars.i18n.selectVariation}</option>`,!n){e.innerHTML=`<option value="">${digicommerceVars.i18n.selectProductFirst}</option>`,e.disabled=!0;return}let i=t.querySelector(`option[value="${n}"]`);if(!i){e.disabled=!0;return}let r=[];try{let o=i.getAttribute("data-variations");o&&(r=JSON.parse(o).filter(c=>c.license_enabled))}catch(o){console.error("Error parsing variations data:",o),e.innerHTML=`<option value="">${digicommerceVars.i18n.errorLoadingVariations}</option>`,e.disabled=!0;return}if(r.length===0&&window.digicommerceProductVariations&&window.digicommerceProductVariations[n]&&(r=window.digicommerceProductVariations[n]),r.length===0){e.innerHTML=`<option value="">${digicommerceVars.i18n.noLicensedVariations}</option>`,e.disabled=!0;return}e.disabled=!1,r.forEach(o=>{let a=document.createElement("option");a.value=o.id||"",a.textContent=o.name||digicommerceVars.i18n.unnamedVariation,e.appendChild(a)})}function W(){document.querySelectorAll(".upgrade-path-item").forEach((e,n)=>{let i=e.querySelector(".path-number");i&&(i.textContent=n+1),e.querySelectorAll("input, select").forEach(o=>{o.name&&(o.name=o.name.replace(/\[\d+\]/,"["+n+"]")),o.classList.contains("target-product-select")&&o.setAttribute("data-index",n)}),e.dataset.index=n})}function X(){let t=document.querySelector(".add-contributor"),e=document.querySelector(".contributors-list"),n=document.getElementById("contributor-template");!t||!e||!n||(t.addEventListener("click",function(){let r=e.querySelectorAll(".contributor-item").length,o=n.innerHTML;o=o.replace(/\{\{INDEX\}\}/g,r);let a=document.createElement("div");a.innerHTML=o;let c=a.firstElementChild;e.appendChild(c),Q(c)}),document.querySelectorAll(".remove-contributor").forEach(i=>{i.addEventListener("click",function(){confirm(digicommerceVars.i18n.removeConfirm)&&this.closest(".contributor-item").remove()})}))}function K(){let t=document.querySelector("#post_ID")?.value,e=digicommerceVars.checkout_url;if(!t||!e)return;let n=document.querySelector(".digi-direct-url");if(n){let i=new URL(e);i.searchParams.set("id",t),n.value=i.toString(),q(n)}x()}function x(){let t=document.querySelector("#post_ID")?.value,e=digicommerceVars.checkout_url;if(!t||!e)return;document.querySelectorAll(".digi-direct-url-variation").forEach((i,r)=>{let o=new URL(e);o.searchParams.set("id",t),o.searchParams.set("variation",r+1),i.value=o.toString(),q(i)})}function q(t){let n=t.closest(".digi-url-field-wrapper").querySelector(".digi-url-tooltip");t.addEventListener("click",async function(){try{navigator.clipboard&&navigator.clipboard.writeText?await navigator.clipboard.writeText(this.value):(this.select(),this.setSelectionRange(0,99999),document.execCommand("copy")),n.textContent=digicommerceVars.i18n.linkCopied||"Link copied",setTimeout(()=>{n.textContent=digicommerceVars.i18n.clickToCopy||"Click to copy"},2e3)}catch(i){console.error("Failed to copy:",i),n.textContent="Copy failed - please select and copy manually",setTimeout(()=>{n.textContent=digicommerceVars.i18n.clickToCopy||"Click to copy"},3e3)}}),t.addEventListener("mouseenter",function(){n.style.display="block"}),t.addEventListener("mouseleave",function(){n.style.display="none"})}function Q(t){let e=t.querySelector(".remove-contributor");e&&e.addEventListener("click",function(){confirm(digicommerceVars.i18n.removeConfirm)&&t.remove()})}function Y(t,e){if(typeof wp>"u"||!wp.media){console.error("WordPress media uploader not available");return}let n;t==="gallery"?n=wp.media({title:digicommerceVars.i18n.selectImages,button:{text:digicommerceVars.i18n.useImages},multiple:!0,library:{type:"image"}}):n=wp.media({title:digicommerceVars.i18n.selectFile,button:{text:digicommerceVars.i18n.useFile},multiple:!1}),n.on("select",function(){if(t==="gallery"){let i=n.state().get("selection").toJSON();e(i)}else{let i=n.state().get("selection").first().toJSON();e(i)}}),n.open()}function f(t){let e=document.querySelector(".digicommerce-upload-progress");e||(e=document.createElement("div"),e.className="digicommerce-upload-progress",e.style.cssText=`
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 20px;
                border-radius: 5px;
                z-index: 9999;
            `,document.body.appendChild(e)),e.textContent=t,e.style.display="block"}function d(){let t=document.querySelector(".digicommerce-upload-progress");t&&(t.style.display="none")}function l(t,e="info"){let n=document.createElement("div");n.className=`notice notice-${e} is-dismissible`,n.innerHTML=`
            <p>${t}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">${digicommerceVars.i18n.dismissNotice}</span>
            </button>
        `;let i=document.querySelector(".wrap")||document.querySelector(".postbox-container");i&&i.insertBefore(n,i.firstChild),setTimeout(function(){n.parentNode&&n.parentNode.removeChild(n)},5e3);let r=n.querySelector(".notice-dismiss");r&&r.addEventListener("click",function(){n.parentNode.removeChild(n)})}})();})();
