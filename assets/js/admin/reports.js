(()=>{document.addEventListener("DOMContentLoaded",function(){let u=null,s=new URLSearchParams(window.location.search).get("tab")||"overview",c="this_month",n=null,d=null,y=document.getElementById("revenueChart"),f=document.querySelector(".digi-date-range"),h=document.querySelector(".custom-date-range"),g=document.querySelector(".start-date"),x=document.querySelector(".end-date"),v=document.querySelector(".apply-custom-range"),b=new Date;g.valueAsDate=new Date(b.getFullYear(),b.getMonth(),1),x.valueAsDate=b,f.addEventListener("change",function(){c=this.value,this.value==="custom"?h.classList.remove("hidden"):(h.classList.add("hidden"),n=null,d=null,m(this.value))}),v.addEventListener("click",function(){g.value&&x.value&&(n=g.value,d=x.value,m("custom",{start:n,end:d}))});function l(e){let a=new Intl.NumberFormat("en-US",{style:"currency",currency:digicommerceReports.currency}).format(e),r=a.replace(/[^\d.,]/g,""),t=a.replace(/[\d.,]/g,"").trim();return digicommerceReports.currency_position==="left"?`${t}${r}`:`${r}${t}`}function p(e,a){let r=document.querySelector(e);r&&(r.textContent=typeof a=="number"&&!e.includes("orders")?l(a):a.toLocaleString())}function $(){let e=document.querySelector(".stats-overview"),a=document.querySelector(".charts-container"),r=document.querySelector(".tab-content");e&&(e.style.display=s==="overview"?"grid":"none"),a&&(a.style.display=s==="overview"?"block":"none"),s==="overview"?document.querySelectorAll(".stat-card").forEach(t=>{let o=t.querySelector('[class*="-amount"]');o&&(o.textContent=digicommerceReports.i18n.loading)}):r&&(r.innerHTML=`<div class="text-center py-8">${digicommerceReports.i18n.loading}</div>`)}function _(){let e=document.querySelector(".charts-container");e&&(e.style.opacity="1");let a=document.querySelector(".tab-content");a&&a.innerHTML===`<div class="text-center py-8">${digicommerceReports.i18n.loading}</div>`&&(a.innerHTML="")}function R(e){p(".revenue-amount",e.revenue),p(".net-amount",e.net),p(".orders-amount",e.orders),p(".average-amount",e.average)}function w(e){let a={type:"line",data:{labels:e.labels,datasets:[{label:digicommerceReports.i18n.revenue,data:e.revenue,borderColor:getComputedStyle(document.documentElement).getPropertyValue("--dc-gold").trim(),backgroundColor:getComputedStyle(document.documentElement).getPropertyValue("--dc-gold").trim()+"20",tension:.4,fill:!0}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,ticks:{callback:function(r){return l(r)}},grid:{display:!0,drawBorder:!1,color:"rgba(0, 0, 0, 0.05)"}},x:{grid:{display:!1}}},plugins:{legend:{display:!1},tooltip:{backgroundColor:"rgba(255, 255, 255, 0.9)",titleColor:"#000",titleFont:{size:14,weight:"bold"},bodyColor:"#666",bodyFont:{size:13},padding:12,borderColor:"rgba(0, 0, 0, 0.1)",borderWidth:1,callbacks:{label:function(r){return l(r.raw)}}}},interaction:{intersect:!1,mode:"index"}}};u&&u.destroy(),u=new Chart(y,a)}function S(e){let a=document.querySelector(".tab-content");if(!a)return;let r=`
			<div class="overflow-hidden border border-solid border-gray-200 rounded-lg">
				<table class="digicommerce-table min-w-full">
					<thead class="bg-light-blue-bg">
						<tr>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.product}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.orders_header}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.revenue_header}
							</th>
						</tr>
					</thead>
					<tbody class="bg-white">
		`;e.forEach(t=>{r+=`
				<tr class="hover:bg-gray-50">
					<td class="px-6 py-4" data-label="${digicommerceReports.i18n.product}">
						<span class="text-sm text-gray-900">${t.name}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.orders_header}">
						<span class="text-sm text-gray-900">${t.orders}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.revenue_header}">
						<span class="text-sm text-gray-900">${l(t.revenue)}</span>
					</td>
				</tr>
			`}),r+=`
					</tbody>
				</table>
			</div>
		`,a.innerHTML=r}function k(e){let a=document.querySelector(".tab-content");if(!a)return;let r=`
			<div class="overflow-hidden border border-solid border-gray-200 rounded-lg">
				<table class="digicommerce-table min-w-full">
					<thead class="bg-light-blue-bg">
						<tr>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.country}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.vat_rate}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.orders_header}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.vat_amount}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.total_amount}
							</th>
						</tr>
					</thead>
					<tbody class="bg-white">
		`;e.forEach(t=>{r+=`
				<tr class="hover:bg-gray-50">
					<td class="px-6 py-4" data-label="${digicommerceReports.i18n.country}">
						<span class="text-sm text-gray-900">${digicommerceReports.countries[t.country]?.name||t.country}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.vat_rate}">
						<span class="text-sm text-gray-900">${digicommerceReports.countries[t.country]?(digicommerceReports.countries[t.country].tax_rate*100).toFixed(1):t.vat_rate}%</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.orders_header}">
						<span class="text-sm text-gray-900">${t.orders}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.vat_amount}">
						<span class="text-sm text-gray-900">${l(t.vat_amount)}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.total_amount}">
						<span class="text-sm text-gray-900">${l(t.total_amount)}</span>
					</td>
				</tr>
			`}),r+=`
					</tbody>
				</table>
			</div>
		`,a.innerHTML=r}function L(e){let a=document.querySelector(".tab-content");if(!a)return;let r=`
			<div class="mb-8">
				<h3 class="text-lg font-medium mb-4">${digicommerceReports.i18n.customer_lifetime}</h3>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div class="bg-light-blue p-4 rounded-lg">
						<h4 class="text-dark-blue mb-2">${digicommerceReports.i18n.avg_lifetime}</h4>
						<p class="text-2xl font-bold">${l(e.lifetime_value.avg_lifetime_value)}</p>
					</div>
					<div class="bg-light-blue p-4 rounded-lg">
						<h4 class="text-dark-blue mb-2">${digicommerceReports.i18n.max_lifetime}</h4>
						<p class="text-2xl font-bold">${l(e.lifetime_value.max_lifetime_value)}</p>
					</div>
				</div>
			</div>
			<div class="overflow-hidden border border-solid border-gray-200 rounded-lg">
				<table class="digicommerce-table min-w-full">
					<thead class="bg-light-blue-bg">
						<tr>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.customer}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.orders_header}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.total_spent}
							</th>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.last_order}
							</th>
						</tr>
					</thead>
					<tbody class="bg-white">
		`;e.top_customers.forEach(t=>{r+=`
				<tr class="hover:bg-gray-50">
					<td class="px-6 py-4" data-label="${digicommerceReports.i18n.customer}">
						<span class="text-sm text-gray-900">${t.name}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.orders_header}">
						<span class="text-sm text-gray-900">${t.orders}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.total_spent}">
						<span class="text-sm text-gray-900">${l(t.total_spent)}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.last_order}">
						<span class="text-sm text-gray-900">${new Date(t.last_order).toLocaleDateString()}</span>
					</td>
				</tr>
			`}),r+=`
					</tbody>
				</table>
			</div>
		`,a.innerHTML=r}function C(e){let a=document.querySelector(".tab-content");if(!a)return;let r=`
			<div class="overflow-hidden border border-solid border-gray-200 rounded-lg">
				<table class="digicommerce-table min-w-full">
					<thead class="bg-light-blue-bg">
						<tr>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.coupon_code}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.usage_count}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.total_discount}
							</th>
						</tr>
					</thead>
					<tbody class="bg-white">
		`;e.usage&&e.usage.length>0?e.usage.forEach(t=>{r+=`
					<tr class="hover:bg-gray-50">
						<td class="px-6 py-4" data-label="${digicommerceReports.i18n.coupon_code}">
							<span class="text-sm text-gray-900">${t.code}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.usage_count}">
							<span class="text-sm text-gray-900">${t.usage_count}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.total_discount}">
							<span class="text-sm text-gray-900">${l(t.total_discount)}</span>
						</td>
					</tr>
				`}):r+=`
				<tr>
					<td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
						${digicommerceReports.i18n.no_data}
					</td>
				</tr>
			`,r+=`
					</tbody>
				</table>
			</div>
		`,a.innerHTML=r}function q(e){let a=document.querySelector(".tab-content");if(!a)return;let r=`
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.subscription_count}</h3>
					<p class="text-2xl font-bold">${e.stats.total_subscriptions}</p>
				</div>
				
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.active_subscriptions}</h3>
					<p class="text-2xl font-bold">${e.stats.active_subscriptions}</p>
				</div>
				
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.mrr}</h3>
					<p class="text-2xl font-bold">${l(e.stats.mrr)}</p>
				</div>
				
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.churn_rate}</h3>
					<p class="text-2xl font-bold">${e.stats.churn_rate}%</p>
				</div>
			</div>
		`;r+=`
			<div class="overflow-hidden border border-solid border-gray-200 rounded-lg">
				<table class="digicommerce-table min-w-full">
					<thead class="bg-light-blue-bg">
						<tr>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.subscription_status}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.subscription_count}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.revenue}
							</th>
						</tr>
					</thead>
					<tbody class="bg-white">
		`,e.stats.status_breakdown&&e.stats.status_breakdown.length>0?e.stats.status_breakdown.forEach(t=>{r+=`
					<tr class="hover:bg-gray-50">
						<td class="px-6 py-4" data-label="${digicommerceReports.i18n.subscription_status}">
							<span class="text-sm text-gray-900">${t.status}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.subscription_count}">
							<span class="text-sm text-gray-900">${t.count}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.revenue}">
							<span class="text-sm text-gray-900">${l(t.revenue)}</span>
						</td>
					</tr>
				`}):r+=`
				<tr>
					<td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
						${digicommerceReports.i18n.no_data}
					</td>
				</tr>
			`,r+=`
					</tbody>
				</table>
			</div>
		`,a.innerHTML=r}function E(e){let a=document.querySelector(".tab-content");if(!a)return;let r=`
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.total_abandoned}</h3>
					<p class="text-2xl font-bold">${e.stats.total_abandoned}</p>
				</div>
	
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.total_recovered}</h3>
					<p class="text-2xl font-bold">${e.stats.total_recovered}</p>
				</div>
	
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.recovery_rate}</h3>
					<p class="text-2xl font-bold">${e.stats.recovery_rate}%</p>
				</div>
			</div>
	
			<div class="overflow-hidden border border-solid border-gray-200 rounded-lg">
				<table class="digicommerce-table min-w-full">
					<thead class="bg-light-blue-bg">
						<tr>
							<th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.customer_email}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.order_id}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.recovered_date}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.revenue}
							</th>
							<th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-bold text-dark-blue">
								${digicommerceReports.i18n.coupon_used}
							</th>
						</tr>
					</thead>
					<tbody class="bg-white">
		`;e.stats.recovered_carts&&e.stats.recovered_carts.length>0?e.stats.recovered_carts.forEach(t=>{r+=`
					<tr class="hover:bg-gray-50">
						<td class="px-6 py-4" data-label="${digicommerceReports.i18n.customer_email}">
							<span class="text-sm text-gray-900">${t.email}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.order_id}">
							<span class="text-sm text-gray-900">#${t.order_id}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.recovered_date}">
							<span class="text-sm text-gray-900">${new Date(t.recovered_at).toLocaleDateString()}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.revenue}">
							<span class="text-sm text-gray-900">${l(t.total)}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.coupon_used}">
							<span class="text-sm text-gray-900">${t.coupon_used||"-"}</span>
						</td>
					</tr>
				`}):r+=`
				<tr>
					<td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
						${digicommerceReports.i18n.no_data}
					</td>
				</tr>
			`,r+=`
					</tbody>
				</table>
			</div>
		`,a.innerHTML=r}function m(e,a=null){let r={action:`digicommerce_reports_${s}`,nonce:digicommerceReports.nonce,range:e};a&&(r.start_date=a.start,r.end_date=a.end),$(),fetch(digicommerceReports.ajaxurl,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams(r)}).then(t=>t.json()).then(t=>{if(!t.success)throw new Error(t.data||"Error loading report data");s==="overview"?t.data.summary&&(R(t.data.summary),t.data.summary.chart&&w(t.data.summary.chart)):s==="products"&&Array.isArray(t.data)?S(t.data):s==="customers"&&t.data?L(t.data):s==="taxes"&&t.data.vat_totals?k(t.data.vat_totals):s==="coupons"&&t.data?C(t.data):s==="subscriptions"&&t.data?q(t.data):s==="abandoned_cart"&&t.data&&E(t.data)}).catch(t=>{console.error("Error fetching report data:",t);let o=document.createElement("div");o.className="error-message bg-red-100 text-red-700 p-4 rounded mb-4",o.textContent=digicommerceReports.i18n.error_loading;let i=document.querySelector(".stats-overview");i&&i.before(o),setTimeout(()=>o.remove(),5e3)}).finally(()=>{_()})}document.querySelectorAll(".digicommerce-tab").forEach(e=>{e.addEventListener("click",function(a){if(a.preventDefault(),this.classList.contains("active"))return;document.querySelectorAll(".digicommerce-tab").forEach(T=>{T.classList.remove("active")}),this.classList.add("active");let r=new URL(window.location);r.searchParams.set("tab",this.dataset.tab),window.history.pushState({},"",r),s=this.dataset.tab;let t=document.querySelector(".stats-overview");t&&(t.style.display=s==="overview"?"grid":"none");let o=document.querySelector(".tab-content");o&&(o.innerHTML=""),c==="custom"&&n&&d?m(c,{start:n,end:d}):m(c);let i=document.querySelector(".charts-container");i&&(i.style.display=s==="overview"?"block":"none"),s!=="overview"&&u&&(u.destroy(),u=null)})}),window.addEventListener("popstate",function(){let a=new URLSearchParams(window.location.search).get("tab")||"overview";document.querySelectorAll(".digicommerce-tab").forEach(i=>{i.dataset.tab===a?i.classList.add("active"):i.classList.remove("active")}),s=a;let r=document.querySelector(".tab-content");r&&(r.innerHTML="");let t=document.querySelector(".stats-overview");t&&(t.style.display=s==="overview"?"grid":"none");let o=document.querySelector(".charts-container");o&&(o.style.display=s==="overview"?"block":"none"),c==="custom"&&n&&d?m(c,{start:n,end:d}):m(c)}),m("this_month")});})();
