(()=>{(function(){"use strict";document.readyState==="loading"?document.addEventListener("DOMContentLoaded",g):g();function g(){$(),E(),w(),z(),j(),R(),J(),W(),X()}function $(){let i=document.querySelectorAll('input[name="digi_price_mode"]'),e=document.querySelector(".pricing-single"),n=document.querySelector(".pricing-variations");!i.length||!e||!n||i.forEach(t=>{t.addEventListener("change",function(){this.value==="single"?(e.style.display="block",n.style.display="none"):(e.style.display="none",n.style.display="block")})})}function E(){let i=document.querySelector(".add-variation"),e=document.querySelector(".variations-list"),n=document.getElementById("variation-template");!i||!e||!n||(i.addEventListener("click",function(){let r=e.querySelectorAll(".variation-item").length,o=n.innerHTML;o=o.replace(/\{\{INDEX\}\}/g,r),o=o.replace(/\{\{NUMBER\}\}/g,r+1),e.insertAdjacentHTML("beforeend",o),y(),q()}),document.addEventListener("click",function(t){t.target.classList.contains("remove-variation")&&confirm(digicommerceVars.i18n.removeConfirm)&&(t.target.closest(".variation-item").remove(),y())}),document.addEventListener("input",function(t){(t.target.classList.contains("variation-file-name")||t.target.classList.contains("variation-file-item-name")||t.target.classList.contains("version-number")||t.target.classList.contains("version-changelog"))&&v()}),document.addEventListener("change",function(t){t.target.type==="checkbox"&&t.target.name&&t.target.name.includes("[isDefault]")&&t.target.checked&&document.querySelectorAll('input[name*="[isDefault]"]').forEach(o=>{o!==t.target&&(o.checked=!1)})}),document.addEventListener("click",function(t){t.target.classList.contains("add-variation-file-btn")&&M(t),t.target.classList.contains("remove-variation-file-btn")&&C(t)}))}function y(){document.querySelectorAll(".variation-item").forEach((e,n)=>{e.dataset.index=n;let t=e.querySelector(".variation-number");t&&(t.textContent=n+1),e.querySelectorAll("input, select, textarea").forEach(a=>{a.name&&(a.name=a.name.replace(/\[\d+\]/,`[${n}]`))});let o=e.querySelector(".add-variation-file-btn");o&&(o.dataset.variationIndex=n)})}class I{constructor(){this.isUploading=!1}async uploadFile(e,n,t){let r=e.files[0];if(!r)return;let o=100*1024*1024;if(r.size>o){t&&t(digicommerceVars.i18n.file_too_large);return}let a=["pdf","doc","docx","xls","xlsx","txt","zip","rar","7z","jpg","jpeg","png","gif","svg","mp4","mp3","wav"],c=r.name.split(".").pop().toLowerCase();if(!a.includes(c)){t&&t(digicommerceVars.i18n.invalid_file);return}this.isUploading=!0;try{let s=new FormData;s.append("file",r),s.append("action","digicommerce_upload_file"),s.append("upload_nonce",digicommerceVars.upload_nonce);let p=await(await fetch(digicommerceVars.ajaxurl,{method:"POST",body:s})).json();if(p.success){let Y={id:p.data.id,name:this.formatFileName(r.name),file:p.data.file,type:r.type,size:r.size,itemName:this.formatFileName(r.name),s3:digicommerceVars.s3_enabled,versions:[]};n&&n(Y)}else t&&t(p.data||digicommerceVars.i18n.upload_failed)}catch(s){console.error("Upload error:",s),t&&t(digicommerceVars.i18n.upload_failed)}finally{this.isUploading=!1}}async deleteFile(e,n,t){try{let r=await wp.apiFetch({path:"/wp/v2/digicommerce/delete-file",method:"POST",data:{file:e,is_s3:digicommerceVars.s3_enabled}});if(r.success){let o=r.message;r.status==="not_found"?o=digicommerceVars.s3_enabled?digicommerceVars.i18n.file_removed_s3:digicommerceVars.i18n.file_removed_server:digicommerceVars.s3_enabled&&(o=digicommerceVars.i18n.file_deleted_s3),n&&n(r)}else t&&t(r.message||digicommerceVars.i18n.delete_failed)}catch(r){console.error("Delete error:",r);let o=r.message||digicommerceVars.i18n.delete_failed;digicommerceVars.s3_enabled&&r.message&&r.message.includes("S3")&&(o=digicommerceVars.i18n.s3_delete_failed),t&&t(o)}}formatFileName(e){return e.replace(/\.[^/.]+$/,"").replace(/-/g," ")}formatFileSize(e){if(e===0)return"0 Bytes";let n=1024,t=["Bytes","KB","MB","GB"],r=Math.floor(Math.log(e)/Math.log(n));return parseFloat((e/Math.pow(n,r)).toFixed(2))+" "+t[r]}}let u=new I;function w(){let i=document.querySelector(".upload-file-btn");i&&i.addEventListener("click",F),document.addEventListener("click",function(e){e.target.classList.contains("remove-file-btn")&&k(e)}),document.addEventListener("click",function(e){e.target.classList.contains("add-version-btn")&&U(e),e.target.classList.contains("remove-version-btn")&&P(e),e.target.classList.contains("upload-version-btn")&&_(e)}),document.addEventListener("input",function(e){(e.target.classList.contains("file-name-input")||e.target.classList.contains("file-item-name-input")||e.target.classList.contains("version-number"))&&m()})}function F(){let i=document.createElement("input");i.type="file",i.accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav",i.addEventListener("change",function(){this.files[0]&&(f(digicommerceVars.s3_enabled?digicommerceVars.i18n.s3_uploading:digicommerceVars.i18n.uploading),u.uploadFile(this,function(e){N(e),m(),d(),l(digicommerceVars.i18n.saved,"success")},function(e){d(),l(e,"error")}))}),i.click()}function M(i){let e=i.target.dataset.variationIndex,n=document.createElement("input");n.type="file",n.accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav",n.addEventListener("change",function(){this.files[0]&&(f(digicommerceVars.s3_enabled?digicommerceVars.i18n.s3_uploading:digicommerceVars.i18n.uploading),u.uploadFile(this,function(t){h(e,t),d(),l(digicommerceVars.i18n.saved,"success")},function(t){d(),l(t,"error")}))}),n.click()}function _(i){let e=i.target.closest(".version-item"),n=i.target.closest(".file-item, .variation-file-item"),t=document.createElement("input");t.type="file",t.accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav",t.addEventListener("change",function(){this.files[0]&&(f(digicommerceVars.s3_enabled?digicommerceVars.i18n.s3_uploading:digicommerceVars.i18n.uploading),u.uploadFile(this,function(r){let o=e.querySelector(".version-file");o.value=r.file,m(),d(),l(digicommerceVars.i18n.saved,"success")},function(r){d(),l(r,"error")}))}),t.click()}function k(i){if(!confirm(digicommerceVars.i18n.removeConfirm))return;let e=i.target.closest(".file-item"),n=b(e);f(digicommerceVars.i18n.deleting),u.deleteFile(n,function(t){e.remove(),m(),d(),l(t.message||digicommerceVars.i18n.remove,"success")},function(t){d(),l(t,"error")})}function C(i){if(!confirm(digicommerceVars.i18n.removeConfirm))return;let e=i.target.closest(".variation-file-item"),n=V(e);f(digicommerceVars.i18n.deleting),u.deleteFile(n,function(t){e.remove(),v(),d(),l(t.message||digicommerceVars.i18n.remove,"success")},function(t){d(),l(t,"error")})}function N(i){let e=document.querySelector(".files-container");if(!e)return;let n=e.querySelector("p");n&&n.textContent.includes("No files")&&n.remove();let t=e.children.length,r=H(i,t);e.insertAdjacentHTML("beforeend",r)}function h(i,e){let n=document.querySelector(`.variation-item[data-index="${i}"]`);if(!n)return;let t=n.querySelector(".variation-files-container");if(!t){let c=`
				<div class="variation-files-section">
					<h5>${digicommerceVars.i18n.downloadFiles}</h5>
					<div class="variation-files-container"></div>
					<button type="button" class="button add-variation-file-btn" data-variation-index="${i}">
						${digicommerceVars.i18n.addDownloadFile}
					</button>
				</div>
			`;return n.querySelector(".variation-basic-fields").insertAdjacentHTML("afterend",c),h(i,e)}let r=t.querySelector(".no-variation-files");r&&r.remove();let o=t.children.length,a=A(e,o);t.insertAdjacentHTML("beforeend",a),v()}function v(){document.querySelectorAll(".variation-item").forEach(function(e,n){let t=e.querySelector(".variation-files-container"),r=[];t&&t.querySelectorAll(".variation-file-item").forEach(function(c){let s=V(c);s.file&&(digicommerceVars.license_enabled&&(s.versions=T(c)),r.push(s))});let o=e.querySelector(".variation-files-data");o?o.name=`variations[${n}][files]`:(o=document.createElement("input"),o.type="hidden",o.className="variation-files-data",o.name=`variations[${n}][files]`,e.appendChild(o)),o.value=JSON.stringify(r)})}function T(i){let e=[];return i.querySelectorAll(".version-item").forEach(function(t){let r=t.querySelector(".version-number").value,o=t.querySelector(".version-changelog")?.value||"";r.trim()&&/^\d+\.\d+\.\d+$/.test(r.trim())&&e.push({version:r.trim(),changelog:o.trim(),release_date:new Date().toISOString()})}),e}function H(i,e){return`
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
						<input type="text" class="file-name-input" value="${i.name||""}" placeholder="${digicommerceVars.i18n.fileName}" />
					</div>
					
					<div class="field-group">
						<label>${digicommerceVars.i18n.itemName}</label>
						<input type="text" class="file-item-name-input" value="${i.itemName||""}" placeholder="${digicommerceVars.i18n.itemName}" />
					</div>
					
					<div class="field-group">
						<label>${digicommerceVars.i18n.filePath}</label>
						<input type="text" class="file-path-input" value="${i.file||""}" readonly />
					</div>
					
					${i.size?`
					<div class="field-group">
						<label>${digicommerceVars.i18n.fileSize}</label>
						<span class="file-size">${u.formatFileSize(i.size)}</span>
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
				<input type="hidden" class="file-id" value="${i.id||""}" />
				<input type="hidden" class="file-type" value="${i.type||""}" />
			</div>
		`}function A(i,e){return`
			<div class="variation-file-item" data-file-index="${e}">
				<div class="variation-file-header">
					<span>${i.name||digicommerceVars.i18n.unnamedFile}</span>
					<button type="button" class="button-link-delete remove-variation-file-btn">
						${digicommerceVars.i18n.remove}
					</button>
				</div>
				<div class="variation-file-details">
					<p>
						<label>${digicommerceVars.i18n.fileName}</label>
						<input type="text" class="variation-file-name" value="${i.name||""}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.itemName}</label>
						<input type="text" class="variation-file-item-name" value="${i.itemName||""}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.filePath}</label>
						<input type="text" class="variation-file-path" value="${i.file||""}" readonly />
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
					<input type="hidden" class="variation-file-id" value="${i.id||""}" />
					<input type="hidden" class="variation-file-type" value="${i.type||""}" />
				</div>
			</div>
		`}function b(i){return{id:i.querySelector(".file-id").value,name:i.querySelector(".file-name-input").value,file:i.querySelector(".file-path-input").value,type:i.querySelector(".file-type").value,itemName:i.querySelector(".file-item-name-input").value}}function V(i){return{id:i.querySelector(".variation-file-id").value,name:i.querySelector(".variation-file-name").value,file:i.querySelector(".variation-file-path").value,type:i.querySelector(".variation-file-type").value,itemName:i.querySelector(".variation-file-item-name").value}}function m(){let i=document.querySelector(".files-list"),e=document.querySelector("#digi_files");if(!i||!e)return;let n=[];i.querySelectorAll(".file-item").forEach(function(r){let o=b(r);digicommerceVars.license_enabled&&(o.versions=D(r)),n.push(o)}),e.value=JSON.stringify(n)}function D(i){let e=[];return i.querySelectorAll(".version-item").forEach(function(t){let r=t.querySelector(".version-number").value,o=t.querySelector(".version-changelog").value;r.trim()&&/^\d+\.\d+\.\d+$/.test(r.trim())&&e.push({version:r.trim(),changelog:o.trim(),release_date:new Date().toISOString()})}),e}function U(i){let n=i.target.closest(".file-item, .variation-file-item").querySelector(".versions-container, .variation-file-versions .versions-container"),t=n.querySelector(".no-versions");t&&t.remove();let r=n.children.length,o=B(r);n.insertAdjacentHTML("beforeend",o),n.lastElementChild.querySelector(".version-number").addEventListener("blur",function(){let s=/^\d+\.\d+\.\d+$/;this.value&&!s.test(this.value.trim())&&(l(digicommerceVars.i18n.semanticVersioning,"error"),this.focus())}),m()}function P(i){if(!confirm(digicommerceVars.i18n.removeConfirm))return;i.target.closest(".version-item").remove(),m()}function B(i){return`
			<div class="version-item" data-version-index="${i}">
				<div class="version-header">
					<span class="version-label">${digicommerceVars.i18n.versions} ${i+1}</span>
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
		`}function z(){let i=document.querySelector(".add-feature");i&&(i.addEventListener("click",function(){let e=document.querySelector(".features-list"),n=e.children.length,t=`
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
            `;e.insertAdjacentHTML("beforeend",t)}),document.addEventListener("click",function(e){e.target.classList.contains("remove-feature")&&confirm(digicommerceVars.i18n.removeConfirm)&&e.target.closest(".feature-item").remove()}))}function j(){let i=document.querySelector(".select-gallery");i&&i.addEventListener("click",function(){Q("gallery",function(e){O(e)})})}function O(i){let e=document.querySelector(".gallery-preview"),n=document.querySelector("#digi_gallery"),t=i.map(o=>({id:o.id,url:o.sizes&&o.sizes.thumbnail?o.sizes.thumbnail.url:o.url,alt:o.alt||""}));n.value=JSON.stringify(t);let r='<div class="gallery-images">';t.forEach(o=>{r+=`
                <div class="gallery-image">
                    <img src="${o.url}" alt="${o.alt}" style="max-width: 100px; height: auto;" />
                </div>
            `}),r+="</div>",e.innerHTML=r}function R(){let i=document.querySelector(".add-bundle-product");i&&(i.addEventListener("click",function(){let e=document.querySelector(".bundle-products-list"),n=e.children.length,t=`
                <div class="bundle-product-item">
                    <p>
                        <label>${digicommerceVars.i18n.product}</label>
                        <select name="bundle_products[${n}]">
                            <option value="">${digicommerceVars.i18n.selectProduct}</option>
                        </select>
                    </p>
                    <p>
                        <button type="button" class="button-link-delete remove-bundle-product">${digicommerceVars.i18n.remove}</button>
                    </p>
                </div>
            `;e.insertAdjacentHTML("beforeend",t)}),document.addEventListener("click",function(e){e.target.classList.contains("remove-bundle-product")&&confirm(digicommerceVars.i18n.removeConfirm)&&e.target.closest(".bundle-product-item").remove()}))}function J(){let i=document.querySelector(".add-upgrade-path"),e=document.querySelector(".upgrade-paths-list"),n=document.getElementById("upgrade-path-template");!i||!e||!n||(i.addEventListener("click",function(){let r=e.querySelectorAll(".upgrade-path-item").length,o=r+1,a=n.innerHTML;a=a.replace(/\{\{INDEX\}\}/g,r),a=a.replace(/\{\{NUMBER\}\}/g,o);let c=document.createElement("div");c.innerHTML=a;let s=c.firstElementChild;e.appendChild(s),S(s,!0)}),document.querySelectorAll(".upgrade-path-item").forEach(t=>{S(t,!1)}))}function S(i,e=!1){if(i.dataset.handlersAttached==="true")return;let n=i.querySelector(".remove-upgrade-path");n&&n.addEventListener("click",function(){confirm(digicommerceVars.i18n.removeConfirm)&&(i.remove(),G())});let t=i.querySelector(".include-coupon-checkbox"),r=i.querySelector(".coupon-options");t&&r&&t.addEventListener("change",function(){r.style.display=this.checked?"block":"none"});let o=i.querySelector(".target-product-select"),a=i.querySelector(".target-variation-select");o&&a&&(o.addEventListener("change",function(){L(this,a)}),e&&o.value&&L(o,a)),i.dataset.handlersAttached="true"}function L(i,e){let n=i.value;if(e.innerHTML=`<option value="">${digicommerceVars.i18n.selectVariation}</option>`,!n){e.innerHTML=`<option value="">${digicommerceVars.i18n.selectProductFirst}</option>`,e.disabled=!0;return}let t=i.querySelector(`option[value="${n}"]`);if(!t){e.disabled=!0;return}let r=[];try{let o=t.getAttribute("data-variations");o&&(r=JSON.parse(o).filter(c=>c.license_enabled))}catch(o){console.error("Error parsing variations data:",o),e.innerHTML=`<option value="">${digicommerceVars.i18n.errorLoadingVariations}</option>`,e.disabled=!0;return}if(r.length===0&&window.digicommerceProductVariations&&window.digicommerceProductVariations[n]&&(r=window.digicommerceProductVariations[n]),r.length===0){e.innerHTML=`<option value="">${digicommerceVars.i18n.noLicensedVariations}</option>`,e.disabled=!0;return}e.disabled=!1,r.forEach(o=>{let a=document.createElement("option");a.value=o.id||"",a.textContent=o.name||digicommerceVars.i18n.unnamedVariation,e.appendChild(a)})}function G(){document.querySelectorAll(".upgrade-path-item").forEach((e,n)=>{let t=e.querySelector(".path-number");t&&(t.textContent=n+1),e.querySelectorAll("input, select").forEach(o=>{o.name&&(o.name=o.name.replace(/\[\d+\]/,"["+n+"]")),o.classList.contains("target-product-select")&&o.setAttribute("data-index",n)}),e.dataset.index=n})}function W(){let i=document.querySelector(".add-contributor"),e=document.querySelector(".contributors-list"),n=document.getElementById("contributor-template");!i||!e||!n||(i.addEventListener("click",function(){let r=e.querySelectorAll(".contributor-item").length,o=n.innerHTML;o=o.replace(/\{\{INDEX\}\}/g,r);let a=document.createElement("div");a.innerHTML=o;let c=a.firstElementChild;e.appendChild(c),K(c)}),document.querySelectorAll(".remove-contributor").forEach(t=>{t.addEventListener("click",function(){confirm(digicommerceVars.i18n.removeConfirm)&&this.closest(".contributor-item").remove()})}))}function X(){let i=document.querySelector("#post_ID")?.value,e=digicommerceVars.checkout_url;if(!i||!e)return;let n=document.querySelector(".digi-direct-url");if(n){let t=new URL(e);t.searchParams.set("id",i),n.value=t.toString(),x(n)}q()}function q(){let i=document.querySelector("#post_ID")?.value,e=digicommerceVars.checkout_url;if(!i||!e)return;document.querySelectorAll(".digi-direct-url-variation").forEach((t,r)=>{let o=new URL(e);o.searchParams.set("id",i),o.searchParams.set("variation",r+1),t.value=o.toString(),x(t)})}function x(i){let n=i.closest(".digi-url-field-wrapper").querySelector(".digi-url-tooltip");i.addEventListener("click",async function(){try{await navigator.clipboard.writeText(this.value),n.textContent=digicommerceVars.i18n.linkCopied||"Link copied",setTimeout(()=>{n.textContent=digicommerceVars.i18n.clickToCopy||"Click to copy"},2e3)}catch(t){console.error("Failed to copy:",t)}}),i.addEventListener("mouseenter",function(){n.style.display="block"}),i.addEventListener("mouseleave",function(){n.style.display="none"})}function K(i){let e=i.querySelector(".remove-contributor");e&&e.addEventListener("click",function(){confirm(digicommerceVars.i18n.removeConfirm)&&i.remove()})}function Q(i,e){if(typeof wp>"u"||!wp.media){console.error("WordPress media uploader not available");return}let n;i==="gallery"?n=wp.media({title:digicommerceVars.i18n.selectImages,button:{text:digicommerceVars.i18n.useImages},multiple:!0,library:{type:"image"}}):n=wp.media({title:digicommerceVars.i18n.selectFile,button:{text:digicommerceVars.i18n.useFile},multiple:!1}),n.on("select",function(){if(i==="gallery"){let t=n.state().get("selection").toJSON();e(t)}else{let t=n.state().get("selection").first().toJSON();e(t)}}),n.open()}function f(i){let e=document.querySelector(".digicommerce-upload-progress");e||(e=document.createElement("div"),e.className="digicommerce-upload-progress",e.style.cssText=`
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 20px;
                border-radius: 5px;
                z-index: 9999;
            `,document.body.appendChild(e)),e.textContent=i,e.style.display="block"}function d(){let i=document.querySelector(".digicommerce-upload-progress");i&&(i.style.display="none")}function l(i,e="info"){let n=document.createElement("div");n.className=`notice notice-${e} is-dismissible`,n.innerHTML=`
            <p>${i}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">${digicommerceVars.i18n.dismissNotice}</span>
            </button>
        `;let t=document.querySelector(".wrap")||document.querySelector(".postbox-container");t&&t.insertBefore(n,t.firstChild),setTimeout(function(){n.parentNode&&n.parentNode.removeChild(n)},5e3);let r=n.querySelector(".notice-dismiss");r&&r.addEventListener("click",function(){n.parentNode.removeChild(n)})}})();})();
