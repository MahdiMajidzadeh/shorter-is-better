@extends('template.dash')

@section('title','Visit Log')

@section('header','Visit Log')

@section('page')
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap">
                    <thead>
                    <tr>
                        <th scope="col">id</th>
                        <th scope="col" colspan="4">url / os - osv - browser - bv</th>
                        <th scope="col">short / device</th>
                        <th scope="col">ip / visit</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td rowspan="2">
                                {{ $log->id }}
                            </td>
                            <td colspan="4">
                                {{ $log->shortURL->destination_url }}
                            </td>
                            <td>
                                {{ $log->shortURL->default_short_url }}
                            </td>
                            <td>
                                {{ $log->ip_address }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                {{ $log->operating_system }}
                            </td>
                            <td>
                                {{ $log->operating_system_version }}
                            </td>
                            <td>
                                {{ $log->browser }}
                            </td>
                            <td>
                                {{ $log->browser_version }}
                            </td>
                            <td>
                                {{ $log->device_type }}
                            </td>
                            <td>
                                {{ $log->visited_at }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="my-2">
        {{ $logs->links() }}
    </div>
@endsection
