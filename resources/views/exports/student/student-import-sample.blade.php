<!DOCTYPE html>
<html>

<head>
    <title>Export Data</title>
</head>

<body>
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Gender</th>
                <th>Date of Birth</th>
                <th>National ID Number</th>
                <th colspan="2">Phone</th>
                <th>Company Address</th>
                <th colspan="2">Care giver Phone</th>
                <th>Father Name</th>
                <th>Father National ID Number</th>
                <th colspan="2">Father Phone</th>
                <th>Mother Name</th>
                <th>Mother National ID Number</th>
                <th colspan="2">Mother Phone</th>
                <th>Company Notes</th>
                <th>District Notes</th>
                <th colspan="2">Academic enrollment</th>
                <th colspan="{{ $totalQualificationParameters }}">Previous qualification</th>
            </tr>

            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>

                <th>Code</th>
                <th>Number</th>

                <th></th>

                <th>Code</th>
                <th>Number</th>

                <th></th>
                <th></th>

                <th>Code</th>
                <th>Number</th>

                <th></th>
                <th></th>

                <th>Code</th>
                <th>Number</th>

                <th></th>
                <th></th>
                <th>Semester</th>
                <th>Class</th>
                @foreach ($qualifications as $qualification)
                <th colspan="{{ $qualification->parameters->count() }}">{{ $qualification->name. '(' .
                    $qualification->id . ')' }}</th>
                @endforeach
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>

                <th></th>
                <th></th>

                <th></th>

                <th></th>
                <th></th>

                <th></th>
                <th></th>

                <th></th>
                <th></th>

                <th></th>
                <th></th>

                <th></th>
                <th></th>

                <th></th>
                <th></th>
                <th></th>
                <th></th>

                @foreach ($qualifications as $qualification)
                @foreach ($qualification->parameters as $parameter)
                <th>{{ $parameter->name. ' (' . $parameter->id . ')' }}</th>
                @endforeach
                @endforeach

            </tr>




        </thead>
    </table>
</body>

</html>
