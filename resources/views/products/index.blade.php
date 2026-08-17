<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Product Inventory</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card shadow-sm">

        <div class="card-body">

            <h1 class="mb-4">
                Product Inventory
            </h1>

            <div id="alertContainer"></div>

            <form id="productForm">

                <input
                    type="hidden"
                    id="productId"
                >

                <div class="row g-3">

                    <div class="col-md-4">

                        <label class="form-label">
                            Product name
                        </label>

                        <input
                            type="text"
                            id="productName"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Quantity in stock
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            class="form-control"
                            min="0"
                            required
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Price per item
                        </label>

                        <input
                            type="number"
                            id="price"
                            class="form-control"
                            min="0"
                            step="0.01"
                            required
                        >

                    </div>

                </div>

                <div class="mt-3">

                    <button
                        type="submit"
                        id="submitButton"
                        class="btn btn-primary"
                    >
                        Add Product
                    </button>

                    <button
                        type="button"
                        id="cancelButton"
                        class="btn btn-secondary d-none"
                    >
                        Cancel
                    </button>

                </div>

            </form>

            <hr class="my-4">

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead class="table-dark">

                    <tr>
                        <th>Product name</th>
                        <th>Quantity in stock</th>
                        <th>Price per item</th>
                        <th>Datetime submitted</th>
                        <th>Total value number</th>
                        <th>Actions</th>
                    </tr>

                    </thead>

                    <tbody id="productTable"></tbody>

                    <tfoot>

                    <tr class="fw-bold table-primary">

                        <td colspan="4" class="text-end">
                            Sum total
                        </td>

                        <td id="grandTotal">
                            0.00
                        </td>

                        <td></td>

                    </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>

</div>

<script>

const csrfToken =
    document.querySelector(
        'meta[name="csrf-token"]'
    ).getAttribute('content');

const form =
    document.getElementById('productForm');

const productId =
    document.getElementById('productId');

const productName =
    document.getElementById('productName');

const quantity =
    document.getElementById('quantity');

const price =
    document.getElementById('price');

const table =
    document.getElementById('productTable');

const grandTotal =
    document.getElementById('grandTotal');

const submitButton =
    document.getElementById('submitButton');

const cancelButton =
    document.getElementById('cancelButton');

const alertContainer =
    document.getElementById('alertContainer');


function showAlert(message, type = 'success')
{
    alertContainer.innerHTML = `
        <div class="alert alert-${type}">
            ${message}
        </div>
    `;

    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 3000);
}


async function loadProducts()
{
    const response = await fetch('/products', {
        headers: {
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        showAlert(
            'Unable to load products.',
            'danger'
        );
        return;
    }

    const data = await response.json();

    table.innerHTML = '';

    data.products.forEach(product => {

        const row =
            document.createElement('tr');

        const total =
            Number(product.quantity_in_stock)
            * Number(product.price_per_item);

        row.innerHTML = `
            <td>
                ${escapeHtml(product.product_name)}
            </td>

            <td>
                ${product.quantity_in_stock}
            </td>

            <td>
                ${Number(
                    product.price_per_item
                ).toFixed(2)}
            </td>

            <td>
                ${product.datetime_submitted}
            </td>

            <td>
                ${total.toFixed(2)}
            </td>

            <td>
                <button
                    class="btn btn-sm btn-warning edit-button"
                    data-id="${product.id}"
                >
                    Edit
                </button>

                <button
                    class="btn btn-sm btn-danger delete-button"
                    data-id="${product.id}"
                >
                    Delete
                </button>
            </td>
        `;

        table.appendChild(row);
    });

    grandTotal.textContent =
        Number(data.grand_total).toFixed(2);
}


form.addEventListener(
    'submit',
    async event => {

        event.preventDefault();

        const id = productId.value;

        const payload = {
            product_name:
                productName.value,

            quantity_in_stock:
                quantity.value,

            price_per_item:
                price.value
        };

        const url =
            id
                ? `/products/${id}`
                : '/products';

        const method =
            id
                ? 'PUT'
                : 'POST';

        const response =
            await fetch(url, {

                method,

                headers: {
                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json',

                    'X-CSRF-TOKEN':
                        csrfToken
                },

                body:
                    JSON.stringify(payload)
            });

        const data =
            await response.json();

        if (!response.ok) {

            showAlert(
                data.message ??
                'Validation failed.',
                'danger'
            );

            return;
        }

        showAlert(data.message);

        resetForm();

        await loadProducts();
    }
);


table.addEventListener(
    'click',
    async event => {

        const editButton =
            event.target.closest(
                '.edit-button'
            );

        const deleteButton =
            event.target.closest(
                '.delete-button'
            );

        if (editButton) {

            const id =
                editButton.dataset.id;

            await editProduct(id);
        }

        if (deleteButton) {

            const id =
                deleteButton.dataset.id;

            await deleteProduct(id);
        }
    }
);


async function editProduct(id)
{
    const response =
        await fetch('/products', {
            headers: {
                'Accept':
                    'application/json'
            }
        });

    const data =
        await response.json();

    const product =
        data.products.find(
            item =>
                String(item.id)
                === String(id)
        );

    if (!product) {
        showAlert(
            'Product not found.',
            'danger'
        );
        return;
    }

    productId.value =
        product.id;

    productName.value =
        product.product_name;

    quantity.value =
        product.quantity_in_stock;

    price.value =
        product.price_per_item;

    submitButton.textContent =
        'Update Product';

    cancelButton.classList.remove(
        'd-none'
    );

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}


async function deleteProduct(id)
{
    if (!confirm(
        'Are you sure you want to delete this product?'
    )) {
        return;
    }

    const response =
        await fetch(
            `/products/${id}`,
            {
                method: 'DELETE',

                headers: {
                    'Accept':
                        'application/json',

                    'X-CSRF-TOKEN':
                        csrfToken
                }
            }
        );

    const data =
        await response.json();

    if (!response.ok) {

        showAlert(
            data.message ??
            'Unable to delete product.',
            'danger'
        );

        return;
    }

    showAlert(data.message);

    await loadProducts();
}


cancelButton.addEventListener(
    'click',
    resetForm
);


function resetForm()
{
    form.reset();

    productId.value = '';

    submitButton.textContent =
        'Add Product';

    cancelButton.classList.add(
        'd-none'
    );
}


function escapeHtml(value)
{
    const element =
        document.createElement('div');

    element.textContent = value;

    return element.innerHTML;
}


loadProducts();

</script>

</body>
</html>