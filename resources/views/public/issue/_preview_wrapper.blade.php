<div class="preview-mode">

    @include('public.issue.view-content', [
        'issue' => $issue,
        'comments' => $comments,
        'business' => $business,
        'isPreview' => true,
    ])

</div>
