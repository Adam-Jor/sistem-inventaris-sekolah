<x-app-layout title="Profil" :breadcrumbs="['Profil' => null]">
    <div class="row">
        <div class="col-xl-8 col-lg-10 col-12">
            <div class="card mb-4">
                <div class="card-body p-3 p-md-4">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body p-3 p-md-4">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
