// Filename  : DlgPreviewShop.cpp
// Description: See DlgPreviewShop.h

#include "DlgPreviewShop.h"
#include "DlgViewModel.h"
#include "DlgFashionShopItem.h"

#include "EC_FashionShop.h"
#include "EC_Shop.h"
#include "EC_GameUIMan.h"
#include "EC_IvtrItem.h"
#include "EC_IvtrFlySword.h"
#include "EC_NPC.h"
#include "EC_Player.h"
#include "EC_UIHelper.h"

#include "globaldataman.h"
#include "elementdataman.h"

// ── Command / Event maps ───────────────────────────────────────────────────
// Inherit everything from CDlgFashionShop — no new commands needed here.
AUI_BEGIN_COMMAND_MAP(CDlgPreviewShop, CDlgFashionShop)
AUI_END_COMMAND_MAP()

AUI_BEGIN_EVENT_MAP(CDlgPreviewShop, CDlgFashionShop)
AUI_END_EVENT_MAP()

// ── Constructor ────────────────────────────────────────────────────────────
CDlgPreviewShop::CDlgPreviewShop()
{
}

// ── OnInitDialog ───────────────────────────────────────────────────────────
bool CDlgPreviewShop::OnInitDialog()
{
	if (!CDlgFashionShop::OnInitDialog())
		return false;

	// Hide controls that are only relevant for the fashion (clothing) shop:
	//   3D character render area, gender switch, profession icon,
	//   palette, reset-model and buy-wearing buttons.
	if (m_pImg_Char)        m_pImg_Char->Show(false);
	if (m_pBtn_Male)        m_pBtn_Male->Show(false);
	if (m_pBtn_Female)      m_pBtn_Female->Show(false);
	if (m_pBtn_Palette)     m_pBtn_Palette->Show(false);
	if (m_pBtn_ResetPlayer) m_pBtn_ResetPlayer->Show(false);
	if (m_pBtn_BuyWearing)  m_pBtn_BuyWearing->Show(false);
	if (m_pImage_Prof)      m_pImage_Prof->Show(false);

	return true;
}

// ── OnShowDialog ───────────────────────────────────────────────────────────
void CDlgPreviewShop::OnShowDialog()
{
	// Skip CDlgFashionShop::OnShowDialog because it calls CreatePlayer()
	// which tries to build a 3D character — not needed here.
	// We call the grandparent (CDlgBase) directly, then do our own setup.
	CDlgBase::OnShowDialog();

	if (!m_pFashionShop || !m_pShoppingCart)
	{
		OnCommand("IDCANCEL");
		return;
	}

	const_cast<CECShopBase*>(m_pFashionShop->Shop())->GetFromServer(0, 0);

	UpdateCash();
	UpdateScrollPosition(true);

	// Clear search box
	if (m_pTxt_Search)
		m_pTxt_Search->SetText(_AL(""));
}

// ── OnTick ─────────────────────────────────────────────────────────────────
void CDlgPreviewShop::OnTick()
{
	// Replicate the parts of CDlgFashionShop::OnTick we still need,
	// but skip SetRenderCallback — Img_Char is hidden and we have no player.
	UpdateCash();
	UpdateScrollPosition();
	UpdateSearchList();

	// Ad banner timer (base class handles this — but it reads m_AdCount which
	// may be 0 in our XML, so it's a safe no-op if no ads are configured).
	// We still want the ad buttons to work if the XML author adds ads later,
	// so delegate just that part:
	if (m_AdCount > 1 && m_adTimer.IsTimeArrived())
		ShowAd((m_currentAd + 1) % m_AdCount);
}

// ── SelectFashionShopItem ──────────────────────────────────────────────────
void CDlgPreviewShop::SelectFashionShopItem(int index)
{
	// Let the base class handle highlight / selection state in the item grid
	CDlgFashionShop::SelectFashionShopItem(index);

	UpdateViewModel(index);
}

// ── ShowFalse ──────────────────────────────────────────────────────────────
void CDlgPreviewShop::ShowFalse()
{
	HideViewModel();
	CDlgFashionShop::ShowFalse();
}

// ── UpdateViewModel ────────────────────────────────────────────────────────
void CDlgPreviewShop::UpdateViewModel(int fashionIndex)
{
	CDlgViewModel* pDlgViewModel = dynamic_cast<CDlgViewModel*>(
		GetGameUIMan()->GetDialog("Win_ViewModel"));
	if (!pDlgViewModel)
		return;

	// No item selected — hide the viewer
	if (fashionIndex < 0 || !m_pFashionShop)
	{
		HideViewModel();
		return;
	}

	const CECFashionShop::FashionSelection* pSel = m_pFashionShop->ItemAt(fashionIndex);
	if (!pSel || pSel->itemIndex < 0)
	{
		HideViewModel();
		return;
	}

	const GSHOP_ITEM* pGItem = m_pFashionShop->Shop()->GetItem(pSel->itemIndex);
	if (!pGItem)
	{
		HideViewModel();
		return;
	}

	CECIvtrItem* pItem = CECIvtrItem::CreateItem(pGItem->id, 0, 1);
	if (!pItem)
	{
		HideViewModel();
		return;
	}
	pItem->GetDetailDataFromLocal();

	// Check we can actually extract a model for this item
	if (!CECViewModelCondition::Meet(pItem) || !CECViewModelExtractor::CanExtract(pItem))
	{
		delete pItem;
		HideViewModel();
		return;
	}

	CECModel* pModel = CECViewModelExtractor::Extract(pItem);
	if (!pModel)
	{
		delete pItem;
		HideViewModel();
		return;
	}

	// Determine the idle animation name depending on item class
	const char* szAct = NULL;
	switch (pItem->GetClassID())
	{
	case CECIvtrItem::ICID_PETEGG:
		szAct = CECNPC::GetBaseActionName(CECNPC::ACT_STAND);
		break;

	case CECIvtrItem::ICID_FLYSWORD:
		{
			CECIvtrFlySword* pFlySword = dynamic_cast<CECIvtrFlySword*>(pItem);
			if (pFlySword)
			{
				switch (CECPlayer::FlyMode2WingType(pFlySword->GetDBEssence()->fly_mode))
				{
				case WINGTYPE_WING:
					szAct = "\xb7\xc9\xd0\xd0\xd0\xfcͣ";   // flight hover
					break;
				case WINGTYPE_FLYSWORD:
					szAct = "\xb7ɽ\xa3\xd0\xfcͣ";           // flysword hover
					break;
				case WINGTYPE_DOUBLEWHEEL:
					szAct = "\xd0\xfcͣ";                     // generic hover
					break;
				}
			}
		}
		break;

	default:
		break;
	}

	// Position Win_ViewModel to the right of our window, then show it
	AlignViewModel(pDlgViewModel);
	pDlgViewModel->Show(true);
	GetGameUIMan()->BringWindowToTop(pDlgViewModel);
	pDlgViewModel->SetModel(pModel, szAct);

	delete pItem;
}

// ── HideViewModel ──────────────────────────────────────────────────────────
void CDlgPreviewShop::HideViewModel()
{
	CDlgViewModel* pDlgViewModel = dynamic_cast<CDlgViewModel*>(
		GetGameUIMan()->GetDialog("Win_ViewModel"));
	if (pDlgViewModel && pDlgViewModel->IsShow())
		pDlgViewModel->Show(false);
}

// ── AlignViewModel ─────────────────────────────────────────────────────────
void CDlgPreviewShop::AlignViewModel(CDlgViewModel* pDlg)
{
	// Place Win_ViewModel immediately to the right of this dialog with a
	// small gap, keeping it within the screen.
	POINT ptShop = GetPos();
	int   nShopW  = GetWidth();
	int   nVmW    = pDlg->GetWidth();
	int   nVmH    = pDlg->GetHeight();

	int x = ptShop.x + nShopW + 4;
	int y = ptShop.y;

	// Get viewport size to clamp
	A3DVIEWPORTPARAM* pVP = m_pA3DEngine->GetActiveViewport()->GetParam();
	if (pVP)
	{
		int maxX = (int)pVP->Width  - nVmW;
		int maxY = (int)pVP->Height - nVmH;
		if (x > maxX) x = ptShop.x - nVmW - 4; // fall back to the left side
		if (x < 0)    x = 0;
		if (y > maxY) y = maxY;
		if (y < 0)    y = 0;
	}

	pDlg->SetPosEx(x, y);
}
