<!DOCTYPE html>
<html>
<head>
    <title>Laravel 8 Generate PDF From View</title>
    <style>
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 8px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #04AA6D;
  color: white;
}

</style>
</head>
<body>

<div style="text-align: center;">
        <img src="{{ public_path('CALIDIG-SOLUTIONS-LOGO-2-1.png') }}" style="width: 300px; height: 100px">
    </div>
<h1 style="text-align:center;">DSR</h1>
    <table id="customers">
    <thead>
        <tr>
            <th>Employee Name</th>
            <th>Project Name</th>
            <th>Description</th>
            <th>Start Date</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($dsr as $data)
        <tr>
            <td>{{ $data->first_name }}</td>
            <td>{{ $data->project_name }}</td>
            <td>{!! $data->description !!}</td>
            <td>{{ $data->start_date }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
  </body>
</html>