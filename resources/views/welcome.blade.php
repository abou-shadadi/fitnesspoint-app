<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download File</title>
</head>

<body>

    <button onclick="downloadFile()">Download File</button>

    <script>
        function downloadFile() {
        const token = '12|VeNsPjVJF4kIALwuQ6pfkqrG5tZFeYaMDUPTFvT578f1b2a3'; // Your token here



        fetch('https://api.fitnesspoint.rw/storage/student/imports/student_import_sample.xlsx', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`,
                'X-CSRF-TOKEN': '' // Your CSRF token if needed
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.blob();
        })
        .then(blob => {
            // Create a temporary link element to trigger the download
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'downloaded_file'; // You can specify a filename here
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error.message);
        });


    }
    </script>

</body>

</html>
