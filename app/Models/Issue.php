<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'issue_project_id',
        'issue_number',
        'title',
        'url',
        'status',
        'priority',
        'created_by',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'planned_start_at',
        'due_at',
        'complete_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'planned_start_at' => 'datetime',
            'due_at' => 'datetime',
            'complete_at' => 'datetime',
        ];
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_WAITING_REVIEW = 'waiting_review';

    public const STATUS_CUSTOMER_REPLIED = 'customer_replied';

    public const STATUS_DONE = 'done';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public static function getNo(string $businessId): string
    {
        $business = Business::find($businessId);
        $docNo = ($business->business_code ?? 'BUS').'-IMS'.date('Ym').'-';
        $lastNo = self::where('business_id', $businessId)
            ->where('status', '!=', self::STATUS_DRAFT)
            ->count() + 1;

        return $docNo.str_pad((string) $lastNo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Temporary number for draft issue.
     */
    public static function generateDraftIssueNumber(): string
    {
        return 'DRAFT-'.Str::lower((string) Str::uuid());
    }

    public static function getPriorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'ต่ำ',
            self::PRIORITY_MEDIUM => 'ปกติ',
            self::PRIORITY_HIGH => 'เร่งด่วน',
        ];
    }

    public static function getPriorityMeta(?string $priority): array
    {
        return match ($priority) {
            self::PRIORITY_LOW => ['label' => 'ต่ำ', 'class' => 'bg-success-subtle text-success'],
            self::PRIORITY_HIGH => ['label' => 'เร่งด่วน', 'class' => 'bg-danger-subtle text-danger'],
            default => ['label' => 'ปกติ', 'class' => 'bg-warning-subtle text-warning'],
        };
    }

    public static function getStatusMeta(?string $status): array
    {
        return match ($status) {
            self::STATUS_DRAFT => ['label' => 'แบบร่าง', 'class' => 'bg-secondary'],
            self::STATUS_PENDING => ['label' => 'รอรีวิว', 'class' => 'bg-warning'],
            self::STATUS_IN_PROGRESS => ['label' => 'กำลังดำเนินการ', 'class' => 'bg-info'],
            self::STATUS_WAITING_REVIEW => ['label' => 'รอตรวจสอบเพื่อปิดงาน', 'class' => 'bg-primary'],
            self::STATUS_CUSTOMER_REPLIED => ['label' => 'ลูกค้าตอบกลับและรอทีมงานดำเนินการ', 'class' => 'bg-danger'],
            self::STATUS_DONE => ['label' => 'ดำเนินการแล้ว', 'class' => 'bg-success'],
            default => ['label' => $status ?: '-', 'class' => 'bg-secondary'],
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::getPriorityMeta($this->priority)['label'];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusMeta($this->status)['label'];
    }

    public function getPriorityBadgeHtml(): HtmlString
    {
        $meta = self::getPriorityMeta($this->priority);

        return new HtmlString('<span class="badge '.e($meta['class']).'">'.e($meta['label']).'</span>');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function comments()
    {
        return $this->hasMany(IssueComment::class)->latest();
    }

    public function firstComment()
    {
        return $this->hasOne(IssueComment::class)->oldest();
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function issueProject()
    {
        return $this->belongsTo(IssueProject::class, 'issue_project_id')->withTrashed();
    }
}
