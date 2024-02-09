<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <title>Search Example</title>
    <style>
        /* Add your custom styles here */
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            margin-top: 40px;
        }
    
        .container {
            width: 98%; 
            max-width: 94%;
        }
    
        form {
            width: 100%;
        }
    
        input[type="text"] {
            height: 40px;
            width: calc(100% - 10px);
            margin-bottom: 10px;
            border-radius: 3px;
        }
    
        .search {
            display: flex;
        }
    
        .button-styl {
            height: 45px;
            margin-left: 5px;
            border-radius: 3px;
        }
    
        #searchResults {
        margin-top: 20px;
        overflow-x: auto; /* Enable horizontal scrolling for the table */
        }

        table {
            width: 100%; /* Set the table width to 100% */
            border-collapse: collapse;
            margin-top: 20px; /* Move the margin to the table */
            overflow-x: auto; /* Enable horizontal scrolling for the table */
        }
    
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
    
        th {
            position: sticky;
            top: 0;
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<div class="container">
    <form action="/api/search" method="GET" class="flex">
        <div class="search">
            <input type="text" name="query[]" id="query" placeholder="Enter your search query" class="mr-2" required>
            <button class="button-styl" type="submit">Search</button>
        </div>
    </form>

    <div id="searchResults" class="mt-6"></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        const searchResults = document.getElementById('searchResults');

        // Initialize DataTable
        let dataTable;

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(form);
            fetch(form.action + '?' + new URLSearchParams(formData))
                .then(response => response.json())
                .then(data => {
                    // Clear any previous results and destroy DataTable if it exists
                    if (dataTable) {
                        dataTable.destroy();
                    }
                    searchResults.innerHTML = '';

                    if (data.length > 0) {
                        // Create a table with DataTables
                        const table = document.createElement('table');
                        table.setAttribute('id', 'resultTable'); // Add an ID for DataTable initialization

                        // Append the table to the searchResults div
                        searchResults.appendChild(table);

                        // Define column names and titles
                        const columns = [
                            { data: 'barcode', title: 'Barcode', defaultContent: 'null'  },
                            // { data: 'upc', title: 'UPC', defaultContent: '' },
                            // { data: 'asin', title: 'ASIN',  defaultContent: '' },
                            { data: 'title', title: 'Title',  defaultContent: 'null' },
                            {
                                data: 'description',
                                title: 'Description',
                                render: function (data, type, row) {
                                    if (type === 'display') {
                                        const truncatedText = truncateText(data, 10); // Display only the first 10 words
                                        const fullText = escapeHTML(data); 
                                        const showMoreHtml = `<span class="view-more" onclick="toggleText(this)" style="color: blue;">(View More)</span>`;
                                        const showLessHtml = `<span class="view-less" onclick="toggleText(this)" style="display: none; color: blue;">(View Less)</span>`;

                                        return `<div class="truncated-text">${truncatedText}<span class="full-text" style="display:none;"> ${fullText}</span>${showMoreHtml}${showLessHtml}</div>`;
                                    }
                                    return data;
                                },
                            },
                            {
                                data: 'category',
                                title: 'Category',
                                render: function (data, type, row) {
                                    if (type === 'display') {
                                        const truncatedText = truncateText(data, 10); 
                                        const fullText = escapeHTML(data); 
                                        const showMoreHtml = `<span class="view-more" onclick="toggleText(this)" style="color: blue;">(View More)</span>`;
                                        const showLessHtml = `<span class="view-less" onclick="toggleText(this)" style="display: none; color: blue;">(View Less)</span>`;

                                        return `<div class="truncated-text">${truncatedText}<span class="full-text" style="display:none;"> ${fullText}</span>${showMoreHtml}${showLessHtml}</div>`;
                                    }
                                    return data;
                                },
                            },
                            { data: 'manufacturer', title: 'Manufacturer',  defaultContent: '' },
                            { data: 'ingredients', title: 'Ingredients',  defaultContent: '' },
                            { data: 'brand', title: 'Brand',  defaultContent: '' },
                            { data: 'model', title: 'Model' ,  defaultContent: ''},
                            { data: 'weight', title: 'Weight',  defaultContent: ''},
                            { data: 'dimension', title: 'Dimension',  defaultContent: '' },
                            { data: 'price', title: 'Price',  defaultContent: '' },
                            { data: 'lowest_recorded_price', title: 'Lowest Recorded Price',  defaultContent: '' },
                            { data: 'highest_recorded_price', title: 'Highest Recorded Price',  defaultContent: '' },
                            {
                                data: 'images',
                                title: 'Images',
                                render: function (data, type, row) {
                                    if (type === 'display') {
                                        let imagesArray = [];

                                        try {
                                            imagesArray = JSON.parse(data);
                                            if (!Array.isArray(imagesArray)) {
                                                // If parsing fails, treat data as a single image URL
                                                imagesArray = [data];
                                            }
                                        } catch (error) {
                                            // Parsing failed, treat data as a single image URL
                                            imagesArray = [data];
                                        }

                                        if (imagesArray.length > 0) {
                                            const imagesHtml = imagesArray.map(imageUrl => `<img src="${imageUrl}" alt="Image" style="width: 100px; height: auto;">`).join(' ');
                                            return imagesHtml;
                                        }
                                    }

                                    return '';
                                },
                            },
                           
                        ];

                        // Initialize DataTable with options using jQuery
                        dataTable = $('#resultTable').DataTable({
                            data: data.filter(item => item !== null),  // Filter out null values
                            columns: columns,
                        });
                    } else {
                        // If there are no results, display a message
                        const noResultsMessage = document.createElement('p');
                        noResultsMessage.textContent = 'No results found.';
                        searchResults.appendChild(noResultsMessage);
                    }
                })
                .catch(error => {
                    console.error('Error fetching search results:', error);
                });
        });
    });

    function truncateText(text, maxWords) {
    const words = text.split(' ');
    const truncated = words.slice(0, maxWords).join(' ');
    const hasMore = words.length > maxWords;
    return `${truncated}${hasMore ? '...' : ''}`;
}

    function escapeHTML(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    }

    // Add this function to your script
    function toggleText(element) {
        const parentDiv = element.parentElement;
        const fullTextElement = parentDiv.querySelector('.full-text');
        const viewMoreElement = parentDiv.querySelector('.view-more');
        const viewLessElement = parentDiv.querySelector('.view-less');

        if (fullTextElement.style.display === 'none') {
            // If full text is hidden, show it and hide "View More"
            fullTextElement.style.display = 'inline';
            viewMoreElement.style.display = 'none';
            viewLessElement.style.display = 'inline';
        } else {
            // If full text is visible, hide it and show "View More"
            fullTextElement.style.display = 'none';
            viewMoreElement.style.display = 'inline';
            viewLessElement.style.display = 'none';
        }
    }

</script>


</body>
</html>
