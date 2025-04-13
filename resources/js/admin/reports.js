/**
 * DigiCommerce Reports JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let chart = null;
    let currentTab = new URLSearchParams(window.location.search).get('tab') || 'overview';
    let currentRange = 'this_month';
    let customStart = null;
    let customEnd = null;
    
    const ctx = document.getElementById('revenueChart');
    const dateRangeSelect = document.querySelector('.digi-date-range');
    const customDateRange = document.querySelector('.custom-date-range');
    const startDate = document.querySelector('.start-date');
    const endDate = document.querySelector('.end-date');
    const applyCustomRange = document.querySelector('.apply-custom-range');

    // Initialize date inputs with default values
    const today = new Date();
    startDate.valueAsDate = new Date(today.getFullYear(), today.getMonth(), 1);
    endDate.valueAsDate = today;

    // Handle date range selection
    dateRangeSelect.addEventListener('change', function() {
        currentRange = this.value;
        if (this.value === 'custom') {
            customDateRange.classList.remove('masked');
        } else {
            customDateRange.classList.add('masked');
            customStart = null;
            customEnd = null;
            fetchReportData(this.value);
        }
    });

    // Handle custom date range
    applyCustomRange.addEventListener('click', function() {
        if (startDate.value && endDate.value) {
            customStart = startDate.value;
            customEnd = endDate.value;
            fetchReportData('custom', {
                start: customStart,
                end: customEnd
            });
        }
    });

    // Format currency
    function formatCurrency(amount) {
		const formatted = new Intl.NumberFormat('en-US', {
			style: 'currency',
			currency: digicommerceReports.currency
		}).format(amount);
		
		// Remove currency symbol to handle position
		const number = formatted.replace(/[^\d.,]/g, '');
		const symbol = formatted.replace(/[\d.,]/g, '').trim();
		
		return digicommerceReports.currency_position === 'left' ? 
			`${symbol}${number}` : 
			`${number}${symbol}`;
	}

    // Update individual stat card
    function updateStatCard(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = typeof value === 'number' && !selector.includes('orders') 
                ? formatCurrency(value)
                : value.toLocaleString();
        }
    }

    // Show loading state
    function showLoadingState() {
        const statsOverview = document.querySelector('.stats-overview');
        const chartContainer = document.querySelector('.charts-container');
        const tabContent = document.querySelector('.tab-content');

        // Handle overview elements visibility
        if (statsOverview) {
            statsOverview.style.display = currentTab === 'overview' ? 'grid' : 'none';
        }
        if (chartContainer) {
            chartContainer.style.display = currentTab === 'overview' ? 'block' : 'none';
        }

        // Show loading state based on current tab
        if (currentTab === 'overview') {
			document.querySelectorAll('.stat-card').forEach(card => {
				const amount = card.querySelector('[class*="-amount"]');
				if (amount) amount.textContent = digicommerceReports.i18n.loading;
			});
		} else if (tabContent) {
			tabContent.innerHTML = `<div class="text-center py-8">${digicommerceReports.i18n.loading}</div>`;
        }
    }

    // Remove loading state
	function removeLoadingState() {
		const chartContainer = document.querySelector('.charts-container');
		if (chartContainer) {
			chartContainer.style.opacity = '1';
		}

		const tabContent = document.querySelector('.tab-content');
		if (tabContent && tabContent.innerHTML === `<div class="text-center py-8">${digicommerceReports.i18n.loading}</div>`) {
			tabContent.innerHTML = '';
		}
	}

    // Update statistics
    function updateStats(data) {
        updateStatCard('.revenue-amount', data.revenue);
		updateStatCard('.net-amount', data.net);
		updateStatCard('.orders-amount', data.orders);
		updateStatCard('.average-amount', data.average);
    }

    // Update chart
    function updateChart(data) {
        const chartConfig = {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: digicommerceReports.i18n.revenue,
                    data: data.revenue,
                    borderColor: getComputedStyle(document.documentElement)
                        .getPropertyValue('--dc-gold').trim(),
                    backgroundColor: getComputedStyle(document.documentElement)
                        .getPropertyValue('--dc-gold').trim() + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        },
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#000',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyColor: '#666',
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.raw);
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        };

        if (chart) {
            chart.destroy();
        }
        chart = new Chart(ctx, chartConfig);
    }

    // Update products table
	function updateProductsTable(products) {
		const container = document.querySelector('.tab-content');
		if (!container) return;

		let html = `
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
		`;

		products.forEach(product => {
			html += `
				<tr class="hover:bg-gray-50">
					<td class="px-6 py-4" data-label="${digicommerceReports.i18n.product}">
						<span class="text-sm text-gray-900">${product.name}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.orders_header}">
						<span class="text-sm text-gray-900">${product.orders}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.revenue_header}">
						<span class="text-sm text-gray-900">${formatCurrency(product.revenue)}</span>
					</td>
				</tr>
			`;
		});

		html += `
					</tbody>
				</table>
			</div>
		`;

		container.innerHTML = html;
	}

    // Update taxes table
	function updateTaxesTable(taxes) {
		const container = document.querySelector('.tab-content');
		if (!container) return;

		let html = `
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
		`;

		taxes.forEach(tax => {
			html += `
				<tr class="hover:bg-gray-50">
					<td class="px-6 py-4" data-label="${digicommerceReports.i18n.country}">
						<span class="text-sm text-gray-900">${digicommerceReports.countries[tax.country]?.name || tax.country}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.vat_rate}">
						<span class="text-sm text-gray-900">${digicommerceReports.countries[tax.country] ? (digicommerceReports.countries[tax.country].tax_rate * 100).toFixed(1) : tax.vat_rate}%</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.orders_header}">
						<span class="text-sm text-gray-900">${tax.orders}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.vat_amount}">
						<span class="text-sm text-gray-900">${formatCurrency(tax.vat_amount)}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.total_amount}">
						<span class="text-sm text-gray-900">${formatCurrency(tax.total_amount)}</span>
					</td>
				</tr>
			`;
		});

		html += `
					</tbody>
				</table>
			</div>
		`;

		container.innerHTML = html;
	}

	// Update customers table
	function updateCustomersTable(data) {
		const container = document.querySelector('.tab-content');
		if (!container) return;

		let html = `
			<div class="mb-8">
				<h3 class="text-lg font-medium mb-4">${digicommerceReports.i18n.customer_lifetime}</h3>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div class="bg-light-blue p-4 rounded-lg">
						<h4 class="text-dark-blue mb-2">${digicommerceReports.i18n.avg_lifetime}</h4>
						<p class="text-2xl font-bold">${formatCurrency(data.lifetime_value.avg_lifetime_value)}</p>
					</div>
					<div class="bg-light-blue p-4 rounded-lg">
						<h4 class="text-dark-blue mb-2">${digicommerceReports.i18n.max_lifetime}</h4>
						<p class="text-2xl font-bold">${formatCurrency(data.lifetime_value.max_lifetime_value)}</p>
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
		`;

		data.top_customers.forEach(customer => {
			html += `
				<tr class="hover:bg-gray-50">
					<td class="px-6 py-4" data-label="${digicommerceReports.i18n.customer}">
						<span class="text-sm text-gray-900">${customer.name}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.orders_header}">
						<span class="text-sm text-gray-900">${customer.orders}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.total_spent}">
						<span class="text-sm text-gray-900">${formatCurrency(customer.total_spent)}</span>
					</td>
					<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.last_order}">
						<span class="text-sm text-gray-900">${new Date(customer.last_order).toLocaleDateString()}</span>
					</td>
				</tr>
			`;
		});

		html += `
					</tbody>
				</table>
			</div>
		`;

		container.innerHTML = html;
	}

	// Update coupons table
	function updateCouponsTable(data) {
		const container = document.querySelector('.tab-content');
		if (!container) return;

		let html = `
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
		`;

		if (data.usage && data.usage.length > 0) {
			data.usage.forEach(coupon => {
				html += `
					<tr class="hover:bg-gray-50">
						<td class="px-6 py-4" data-label="${digicommerceReports.i18n.coupon_code}">
							<span class="text-sm text-gray-900">${coupon.code}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.usage_count}">
							<span class="text-sm text-gray-900">${coupon.usage_count}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.total_discount}">
							<span class="text-sm text-gray-900">${formatCurrency(coupon.total_discount)}</span>
						</td>
					</tr>
				`;
			});
		} else {
			html += `
				<tr>
					<td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
						${digicommerceReports.i18n.no_data}
					</td>
				</tr>
			`;
		}

		html += `
					</tbody>
				</table>
			</div>
		`;

		container.innerHTML = html;
	}

	// Update subscriptions stats
	function updateSubscriptionsStats(data) {
		const container = document.querySelector('.tab-content');
		if (!container) return;

		// Create stats cards
		let html = `
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.subscription_count}</h3>
					<p class="text-2xl font-bold">${data.stats.total_subscriptions}</p>
				</div>
				
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.active_subscriptions}</h3>
					<p class="text-2xl font-bold">${data.stats.active_subscriptions}</p>
				</div>
				
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.mrr}</h3>
					<p class="text-2xl font-bold">${formatCurrency(data.stats.mrr)}</p>
				</div>
				
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.churn_rate}</h3>
					<p class="text-2xl font-bold">${data.stats.churn_rate}%</p>
				</div>
			</div>
		`;

		// Add status breakdown table
		html += `
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
		`;

		if (data.stats.status_breakdown && data.stats.status_breakdown.length > 0) {
			data.stats.status_breakdown.forEach(status => {
				html += `
					<tr class="hover:bg-gray-50">
						<td class="px-6 py-4" data-label="${digicommerceReports.i18n.subscription_status}">
							<span class="text-sm text-gray-900">${status.status}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.subscription_count}">
							<span class="text-sm text-gray-900">${status.count}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.revenue}">
							<span class="text-sm text-gray-900">${formatCurrency(status.revenue)}</span>
						</td>
					</tr>
				`;
			});
		} else {
			html += `
				<tr>
					<td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
						${digicommerceReports.i18n.no_data}
					</td>
				</tr>
			`;
		}

		html += `
					</tbody>
				</table>
			</div>
		`;

		container.innerHTML = html;
	}

	// Update abandoned cart stats
	function updateAbandonedCartTable(data) {
		const container = document.querySelector('.tab-content');
		if (!container) return;
	
		// Create stats cards
		let html = `
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.total_abandoned}</h3>
					<p class="text-2xl font-bold">${data.stats.total_abandoned}</p>
				</div>
	
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.total_recovered}</h3>
					<p class="text-2xl font-bold">${data.stats.total_recovered}</p>
				</div>
	
				<div class="bg-light-blue p-4 rounded-lg">
					<h3 class="text-dark-blue mb-2">${digicommerceReports.i18n.recovery_rate}</h3>
					<p class="text-2xl font-bold">${data.stats.recovery_rate}%</p>
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
		`;
	
		if (data.stats.recovered_carts && data.stats.recovered_carts.length > 0) {
			data.stats.recovered_carts.forEach(cart => {
				html += `
					<tr class="hover:bg-gray-50">
						<td class="px-6 py-4" data-label="${digicommerceReports.i18n.customer_email}">
							<span class="text-sm text-gray-900">${cart.email}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.order_id}">
							<span class="text-sm text-gray-900">#${cart.order_id}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.recovered_date}">
							<span class="text-sm text-gray-900">${new Date(cart.recovered_at).toLocaleDateString()}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.revenue}">
							<span class="text-sm text-gray-900">${formatCurrency(cart.total)}</span>
						</td>
						<td class="px-6 py-4 ltr:text-right rtl:text-left" data-label="${digicommerceReports.i18n.coupon_used}">
							<span class="text-sm text-gray-900">${cart.coupon_used || '-'}</span>
						</td>
					</tr>
				`;
			});
		} else {
			html += `
				<tr>
					<td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
						${digicommerceReports.i18n.no_data}
					</td>
				</tr>
			`;
		}
	
		html += `
					</tbody>
				</table>
			</div>
		`;
	
		container.innerHTML = html;
	}

	// Fetch report data
    function fetchReportData(range, customDates = null) {
        const data = {
            action: `digicommerce_reports_${currentTab}`,
            nonce: digicommerceReports.nonce,
            range: range
        };

        if (customDates) {
            data.start_date = customDates.start;
            data.end_date = customDates.end;
        }

        showLoadingState();

        fetch(digicommerceReports.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(response => {
            if (!response.success) {
                throw new Error(response.data || 'Error loading report data');
            }
            
            if (currentTab === 'overview') {
                if (response.data.summary) {
                    updateStats(response.data.summary);
                    if (response.data.summary.chart) {
                        updateChart(response.data.summary.chart);
                    }
                }
            } else if (currentTab === 'products' && Array.isArray(response.data)) {
                updateProductsTable(response.data);
            } else if (currentTab === 'customers' && response.data) {
                updateCustomersTable(response.data);
            } else if (currentTab === 'taxes' && response.data.vat_totals) {
                updateTaxesTable(response.data.vat_totals);
            } else if (currentTab === 'coupons' && response.data) {
				updateCouponsTable(response.data);
            } else if (currentTab === 'subscriptions' && response.data) {
				updateSubscriptionsStats(response.data);
            } else if (currentTab === 'abandoned_cart' && response.data) {
				updateAbandonedCartTable(response.data); 
			}
        })
        .catch(error => {
			console.error('Error fetching report data:', error);
			const errorDiv = document.createElement('div');
			errorDiv.className = 'error-message bg-red-100 text-red-700 p-4 rounded mb-4';
			errorDiv.textContent = digicommerceReports.i18n.error_loading;
			const statsOverview = document.querySelector('.stats-overview');
			if (statsOverview) {
				statsOverview.before(errorDiv);
			}
			setTimeout(() => errorDiv.remove(), 5000);
		})
		.finally(() => {
			removeLoadingState();
		});
    }

    // Handle tab changes
    document.querySelectorAll('.digicommerce-tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.classList.contains('active')) return;
            
            document.querySelectorAll('.digicommerce-tab').forEach(t => {
                t.classList.remove('active');
            });
            this.classList.add('active');

            const newUrl = new URL(window.location);
            newUrl.searchParams.set('tab', this.dataset.tab);
            window.history.pushState({}, '', newUrl);

            currentTab = this.dataset.tab;
            
            // Update visibility of stats overview
            const statsOverview = document.querySelector('.stats-overview');
            if (statsOverview) {
                statsOverview.style.display = currentTab === 'overview' ? 'grid' : 'none';
            }

            // Clear tab content when switching tabs
            const tabContent = document.querySelector('.tab-content');
            if (tabContent) {
                tabContent.innerHTML = '';
            }
            
			if (currentRange === 'custom') {
				// Only use custom dates if they've been applied (both values exist)
				if (customStart && customEnd) {
					fetchReportData(currentRange, {
						start: customStart,
						end: customEnd
					});
				} else {
					// Fall back to the default date range if custom dates haven't been applied
					fetchReportData('this_month');
				}
			} else {
				fetchReportData(currentRange);
			}

            // Handle chart visibility
            const chartContainer = document.querySelector('.charts-container');
            if (chartContainer) {
                chartContainer.style.display = currentTab === 'overview' ? 'block' : 'none';
            }
            
            if (currentTab !== 'overview' && chart) {
                chart.destroy();
                chart = null;
            }
        });
    });

    // Handle popstate (browser back/forward)
    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);
        const newTab = params.get('tab') || 'overview';
        
        document.querySelectorAll('.digicommerce-tab').forEach(tab => {
            if (tab.dataset.tab === newTab) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        currentTab = newTab;

        // Clear tab content when switching tabs
        const tabContent = document.querySelector('.tab-content');
        if (tabContent) {
            tabContent.innerHTML = '';
        }

        // Update visibility of stats overview
        const statsOverview = document.querySelector('.stats-overview');
        if (statsOverview) {
            statsOverview.style.display = currentTab === 'overview' ? 'grid' : 'none';
        }

        // Handle chart visibility
        const chartContainer = document.querySelector('.charts-container');
        if (chartContainer) {
            chartContainer.style.display = currentTab === 'overview' ? 'block' : 'none';
        }

        if (currentRange === 'custom' && customStart && customEnd) {
            fetchReportData(currentRange, {
                start: customStart,
                end: customEnd
            });
        } else {
            fetchReportData(currentRange);
        }
    });

    // Initial data fetch
    fetchReportData('this_month');
});