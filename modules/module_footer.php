<?php
$shareTitle = trim((string)($memorial['pet_name'] ?: 'this memorial'));
$inviteHeading = "Invite {$shareTitle}'s family and friends";
$emailSubject = 'Remembering ' . $shareTitle . ' on FurEver Memories';
$emailBody = "I'd like to share this memorial page with you:\n\n" . $publicUrl;
$facebookShareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($publicUrl);
$whatsAppShareUrl = 'https://wa.me/?text=' . rawurlencode("Remembering {$shareTitle}: {$publicUrl}");
$qrCodeUrl = qrcode_data_uri($publicUrl);
$supportInquiryOptions = [
    'Report a problem',
    'Make a suggestion',
    'Share feedback or testimonial',
    'Press inquiries',
    'Help me write',
    'Partnership inquiries',
    'Blog support',
    'Other',
];
?>
<section class="py-4 py-md-5 invite-share-section" id="footer-contact">
    <div class="container invite-share-container">
        <div class="invite-share-stage">
            <div class="invite-share-main">
                <div class="invite-share-card">
                    <div class="invite-share-card-body">
                        <div class="invite-share-copy">
                            <span class="invite-share-kicker">Share this memorial</span>
                            <h2 class="invite-share-title mb-3"><?= e($inviteHeading) ?></h2>
                            <p class="invite-share-subtitle">Invite loved ones, send the link privately, or post the memorial to Facebook in just a few taps.</p>
                            <div class="invite-share-actions">
                                <button class="btn invite-share-primary-btn" type="button" data-bs-toggle="modal" data-bs-target="#inviteShareModal">
                                    <span class="invite-share-btn-icon">&#128101;</span>
                                    <span>Invite now</span>
                                </button>
                                <a href="<?= e($facebookShareUrl) ?>" target="_blank" rel="noopener noreferrer" class="invite-share-inline-link text-decoration-none">
                                    <span class="invite-share-social-icon invite-share-social-icon--facebook">f</span>
                                    <span>Share on Facebook</span>
                                </a>
                            </div>
                        </div>
                        <div class="invite-share-hero-icon" aria-hidden="true">&#128101;</div>
                    </div>
                </div>

                <div class="public-meta-card">
                    <div class="public-meta-grid">
                        <div class="public-meta-item">
                            <div class="public-meta-icon" aria-hidden="true">&#128065;</div>
                            <div class="public-meta-copy">
                                <div class="public-meta-title"><?= e((string)$viewCount) ?> Views</div>
                                <div class="public-meta-caption">People who have visited this memorial page.</div>
                            </div>
                        </div>
                        <div class="public-meta-item">
                            <div class="public-meta-icon" aria-hidden="true">&#127760;</div>
                            <div class="public-meta-copy">
                                <div class="public-meta-title">Open access</div>
                                <div class="public-meta-caption">Family and friends can open the memorial with the shared link.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="public-suggestion-side">
                <div class="public-suggestion-wrap" id="publicSuggestionWrap">
                    <div class="public-feedback-tooltip" id="publicFeedbackTooltip" role="tooltip">
                        To keep getting better, we need your help. Please take a moment to share your experience with this service and ideas for improvement!
                    </div>
                    <button class="public-suggestion-card border-0" id="publicSuggestionCard" type="button" data-bs-toggle="modal" data-bs-target="#supportContactModal">
                        <div class="public-suggestion-kicker">Support</div>
                        <div class="public-suggestion-title">Have a suggestion?</div>
                        <div class="public-suggestion-copy">Tell us what would make this memorial experience even more thoughtful and comforting.</div>
                        <div class="public-suggestion-link">Contact us <span aria-hidden="true">&rarr;</span></div>
                    </button>
                </div>
            </aside>
        </div>
    </div>
</section>

<section class="py-4 footer-section text-center text-white">
    <div class="container">
        <div class="mb-2"><?= render_rich_text($memorial['share_footer_text'] ?: 'Created with love through FurEver Memories') ?></div>
        <div class="small opacity-75">Share this memorial page with family and friends.</div>
    </div>
</section>

<div class="modal fade" id="inviteShareModal" tabindex="-1" aria-labelledby="inviteShareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content invite-modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title invite-modal-title" id="inviteShareModalLabel">How would you like to send invitations?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="invite-modal-actions">
                    <a href="mailto:?subject=<?= rawurlencode($emailSubject) ?>&body=<?= rawurlencode($emailBody) ?>" class="invite-modal-action text-decoration-none">
                        <span class="invite-modal-action-icon invite-modal-action-icon--email">&#9993;</span>
                        <span>Invite by Email</span>
                    </a>
                    <a href="<?= e($facebookShareUrl) ?>" target="_blank" rel="noopener noreferrer" class="invite-modal-action text-decoration-none">
                        <span class="invite-modal-action-icon invite-modal-action-icon--facebook">f</span>
                        <span>Post to Your Timeline</span>
                    </a>
                    <a href="<?= e($whatsAppShareUrl) ?>" target="_blank" rel="noopener noreferrer" class="invite-modal-action text-decoration-none">
                        <span class="invite-modal-action-icon invite-modal-action-icon--whatsapp">&#9990;</span>
                        <span>Invite with WhatsApp</span>
                    </a>
                    <a href="<?= e($qrCodeUrl) ?>" target="_blank" rel="noopener noreferrer" class="invite-modal-action text-decoration-none">
                        <span class="invite-modal-action-icon invite-modal-action-icon--qr">&#9638;</span>
                        <span>Get QR code</span>
                    </a>
                </div>

                <div class="invite-modal-copy mt-4">
                    <label class="invite-modal-copy-label">Copy link</label>
                    <div class="invite-modal-copy-box">
                        <input type="text" class="form-control border-0 bg-transparent" value="<?= e($publicUrl) ?>" readonly>
                        <button class="btn btn-link text-decoration-none fw-semibold" type="button" onclick="navigator.clipboard.writeText('<?= e($publicUrl) ?>')">Copy</button>
                    </div>
                    <p class="invite-modal-copy-help mb-0">Share this link to invite people to <?= e($shareTitle) ?>'s memorial.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="supportContactModal" tabindex="-1" aria-labelledby="supportContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content invite-modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title invite-modal-title" id="supportContactModalLabel">Contact customer support</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form method="post" action="<?= e($publicUrl) ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="support_contact">
                    <div class="mb-3">
                        <label class="form-label support-form-label">Type of inquiry</label>
                        <select name="support_inquiry_type" class="form-select support-form-control" required>
                            <?php foreach ($supportInquiryOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= $option === 'Make a suggestion' ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label support-form-label">Your name</label>
                        <input type="text" name="support_name" class="form-control support-form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label support-form-label">Email address</label>
                        <input type="email" name="support_email" class="form-control support-form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label support-form-label">Subject</label>
                        <input type="text" name="support_subject" class="form-control support-form-control" placeholder="Please add your subject here" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label support-form-label">Message</label>
                        <textarea name="support_message" class="form-control support-form-control support-form-textarea" placeholder="Please enter the specific details of your request. Please provide as much information as possible so we can help you quickly." required></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn support-submit-btn" type="submit">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
