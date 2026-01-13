<div class="modal-header">
    <h5 class="modal-title">
        <code class="me-2">{{ $commit->short_hash }}</code>
        Commit Details
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <!-- Author & Time -->
    <div class="d-flex align-items-center mb-4">
        <div class="avatar avatar-sm bg-label-primary rounded-circle me-3">
            <span class="avatar-initial">{{ strtoupper(substr($commit->author_name, 0, 2)) }}</span>
        </div>
        <div>
            <div class="fw-semibold">{{ $commit->author_name }}</div>
            <small class="text-muted">{{ $commit->committed_at->format('F j, Y g:i A') }}</small>
        </div>
    </div>

    <!-- Commit Message -->
    <div class="mb-4">
        <h6 class="mb-2">Commit Message</h6>
        <div class="bg-light p-3 rounded" style="white-space: pre-wrap;">{{ $commit->message }}</div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-4 text-center">
            <div class="h4 text-success mb-0">+{{ number_format($commit->additions) }}</div>
            <small class="text-muted">Additions</small>
        </div>
        <div class="col-4 text-center">
            <div class="h4 text-danger mb-0">-{{ number_format($commit->deletions) }}</div>
            <small class="text-muted">Deletions</small>
        </div>
        <div class="col-4 text-center">
            <div class="h4 mb-0">{{ $commit->files_count }}</div>
            <small class="text-muted">Files Changed</small>
        </div>
    </div>

    <!-- Changed Files -->
    @if($commit->files_count > 0)
        <div class="mb-4">
            <h6 class="mb-2">Changed Files</h6>
            <ul class="list-group list-group-flush">
                @foreach($commit->files_changed ?? [] as $file)
                    <li class="list-group-item py-2 px-0">
                        <code class="small">{{ $file }}</code>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Full Hash & Link -->
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <small class="text-muted">Full Hash:</small>
            <code class="ms-2 small">{{ $commit->commit_hash }}</code>
        </div>
        @if($commit->bitbucket_url)
            <a href="{{ $commit->bitbucket_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-external-link me-1"></i>View on Bitbucket
            </a>
        @endif
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
</div>
