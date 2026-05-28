// Filename	: DlgWikiMonsterDrop.cpp
// Creator	: Xu Wenbin
// Date		: 2010/04/07

#include "DlgWikiMonsterDrop.h"
#include "EC_GameUIMan.h"
#include "EC_Game.h"
#include "EC_GameRun.h"
#include "AUIImagePicture.h"
#include "AUIProgress.h"
#include "DlgWikiEquipment.h"
#include "DlgWikiItem.h"
#include "WikiSearchCommand.h"
#include "EC_IvtrItem.h"
#include "elementdataman.h"
#include "AFI.h"
#include <set>

class WikiSearchAcceptAll : public WikiSearcher::SearchCommand
{
public:
	virtual bool operator()(WikiEntityPtr) const { return true; }
	virtual SearchCommand* Clone() const { return new WikiSearchAcceptAll(); }
};

#define new A_DEBUG_NEW

AUI_BEGIN_COMMAND_MAP(CDlgWikiMonsterDrop, CDlgWikiItemListBase)
AUI_END_COMMAND_MAP()

AUI_BEGIN_EVENT_MAP(CDlgWikiMonsterDrop, CDlgWikiItemListBase)
AUI_END_EVENT_MAP()

CDlgWikiMonsterDrop::CDlgWikiMonsterDrop()
{
	memset(m_pIcons,           0, sizeof(m_pIcons));
	memset(m_pProgress,        0, sizeof(m_pProgress));
	memset(m_pDropNumProgress, 0, sizeof(m_pDropNumProgress));
	m_pCachedEssence = NULL;
}

bool CDlgWikiMonsterDrop::OnInitDialog()
{
	bool bRet = CDlgWikiItemListBase::OnInitDialog();

	for(int i = 0; i < MAX_ICONS; i++)
	{
		AString szName;
		szName.Format("Img_Icon_%02d", i + 1);
		m_pIcons[i] = (PAUIIMAGEPICTURE)(GetDlgItem(szName));
		if(m_pIcons[i])
		{
			m_pIcons[i]->SetCover(NULL, -1);
			m_pIcons[i]->Show(false);
		}

		AString szPrgsName;
		szPrgsName.Format("Prgs_%02d", i + 1);
		m_pProgress[i] = (PAUIPROGRESS)(GetDlgItem(szPrgsName));
		if(m_pProgress[i])
		{
			m_pProgress[i]->SetRangeMax(10000);
			m_pProgress[i]->SetProgress(0);
			m_pProgress[i]->Show(false);
		}
	}

	for(int i = 0; i < 4; i++)
	{
		AString szName;
		szName.Format("Prgs_DropNum%d", i);
		m_pDropNumProgress[i] = (PAUIPROGRESS)(GetDlgItem(szName));
		if(m_pDropNumProgress[i])
		{
			m_pDropNumProgress[i]->SetRangeMax(10000);
			m_pDropNumProgress[i]->SetProgress(0);
			m_pDropNumProgress[i]->Show(false);
		}
	}

	return bRet;
}

void CDlgWikiMonsterDrop::OnShowDialog()
{
	CDlgWikiItemListBase::OnShowDialog();
	UpdateStatsDisplay(m_pCachedEssence);
}

void CDlgWikiMonsterDrop::SetSearchDataPtr(void* ptr)
{
	a_LogOutput(1, "WikiDrop: SetSearchDataPtr ptr=%p", ptr);

	m_pCachedEssence = (const MONSTER_ESSENCE*)(ptr);
	m_filteredDrop.clear();

	if(m_pCachedEssence)
	{
		std::set<unsigned int> seen;
		int maxDrop = (int)(sizeof(m_pCachedEssence->drop_matters)/sizeof(m_pCachedEssence->drop_matters[0]));
		for(int i = 0; i < maxDrop; i++)
		{
			unsigned int id = m_pCachedEssence->drop_matters[i].id;
			if(!id) continue;
			if(seen.count(id)) continue;
			seen.insert(id);
			DropEntry e;
			e.id          = m_pCachedEssence->drop_matters[i].id;
			e.probability = m_pCachedEssence->drop_matters[i].probability;
			m_filteredDrop.push_back(e);
		}
	}

	int filteredCount = (int)m_filteredDrop.size();
	SetContentProvider(!m_pCachedEssence ? NULL : &WikiSerialDataProvider(0, filteredCount));
	SetSearchCommand(!m_pCachedEssence ? NULL : &WikiSearchAcceptAll());

	CDlgWikiBase::SetSearchDataPtr(ptr);
}

void CDlgWikiMonsterDrop::UpdateStatsDisplay(const MONSTER_ESSENCE* pEssence)
{
	PAUILABEL p;

	p = dynamic_cast<PAUILABEL>(GetDlgItem("Val_Exp"));
	if(p)
	{
		if(!pEssence) p->SetText(_AL(""));
		else { ACString s; s.Format(_AL("%d"), (int)pEssence->exp); p->SetText(s); }
	}

	p = dynamic_cast<PAUILABEL>(GetDlgItem("Val_Sp"));
	if(p)
	{
		if(!pEssence) p->SetText(_AL(""));
		else { ACString s; s.Format(_AL("%d"), (int)pEssence->skillpoint); p->SetText(s); }
	}

	p = dynamic_cast<PAUILABEL>(GetDlgItem("Val_Money"));
	if(p)
	{
		if(!pEssence) p->SetText(_AL(""));
		else { ACString s; s.Format(_AL("%d"), (int)pEssence->money_average); p->SetText(s); }
	}

	p = dynamic_cast<PAUILABEL>(GetDlgItem("Val_DropTimes"));
	if(p)
	{
		if(!pEssence) p->SetText(_AL(""));
		else { ACString s; s.Format(_AL("%d"), (int)pEssence->drop_times); p->SetText(s); }
	}

	p = dynamic_cast<PAUILABEL>(GetDlgItem("Val_MoneyRange"));
	if(p)
	{
		if(!pEssence) p->SetText(_AL(""));
		else { ACString s; s.Format(_AL("%d"), (int)pEssence->money_var); p->SetText(s); }
	}

	float probs[4] = {
		!pEssence ? 0.0f : pEssence->probability_drop_num0,
		!pEssence ? 0.0f : pEssence->probability_drop_num1,
		!pEssence ? 0.0f : pEssence->probability_drop_num2,
		!pEssence ? 0.0f : pEssence->probability_drop_num3
	};

	for(int i = 0; i < 4; i++)
	{
		// Text label
		AString szName;
		szName.Format("Val_DropNum%d", i);
		p = dynamic_cast<PAUILABEL>(GetDlgItem(szName));
		if(p)
		{
			if(!pEssence) { p->SetText(_AL("")); }
			else
			{
				ACString s;
				float pct = probs[i] * 100.0f;
				if(probs[i] > 0.0f && pct < 0.01f)
					s = _AL("<0.01%");
				else
					s.Format(_AL("%.2f%%"), pct);
				p->SetText(s);
			}
		}

		// Progress bar
		if(m_pDropNumProgress[i])
		{
			if(!pEssence || probs[i] <= 0.0f)
			{
				m_pDropNumProgress[i]->SetProgress(0);
				m_pDropNumProgress[i]->Show(false);
			}
			else
			{
				int val = (int)(probs[i] * 10000.0f);
				if(val < 1)     val = 1;
				if(val > 10000) val = 10000;
				m_pDropNumProgress[i]->SetProgress(val);
				m_pDropNumProgress[i]->Show(true);
			}
		}
	}
}

bool CDlgWikiMonsterDrop::OnModityItem(WikiEntityPtr p, PAUIOBJECT pObj, PAUIOBJECT pName)
{
	const MONSTER_ESSENCE *pEssence = m_pCachedEssence;
	if(!pEssence) return false;

	WikiSerialDataProvider::Entity* pEE = dynamic_cast<WikiSerialDataProvider::Entity*>(p.Get());
	ASSERT(pEE);
	if(!pEE) return false;

	unsigned int iCandidate = pEE->GetID();
	a_LogOutput(1, "WikiDrop: OnModityItem iCandidate=%u pObj=%s", iCandidate, pObj ? pObj->GetName() : "NULL");

	unsigned int id = m_filteredDrop[iCandidate].id;
	if(!id) return false;

	CECIvtrItem *pItem = CECIvtrItem::CreateItem(id, 0, 1);
	if(!pItem) return false;

	if(pItem->GetClassID() == CECIvtrItem::ICID_ERRORITEM)
	{
		delete pItem;
		return false;
	}

	int iObj = 1;
	if(pObj)
	{
		const char* pUnder = strrchr(pObj->GetName(), '_');
		if(pUnder) iObj = atoi(pUnder + 1);
		if(iObj < 1) iObj = 1;
	}

	int iIconIdx = iObj - 1;

	if(iIconIdx >= 0 && iIconIdx < MAX_ICONS && m_pIcons[iIconIdx])
	{
		AString szIconFile;
		af_GetFileTitle(pItem->GetIconFile(), szIconFile);
		szIconFile.MakeLower();
		int nFrame = GetGameUIMan()->GetIconIndex(CECGameUIMan::ICONS_INVENTORY, szIconFile);
		m_pIcons[iIconIdx]->SetCover(
			GetGameUIMan()->m_pA2DSpriteIcons[CECGameUIMan::ICONS_INVENTORY],
			nFrame);
		m_pIcons[iIconIdx]->SetTransparent(false);
		m_pIcons[iIconIdx]->Show(true);
	}

	pObj->Show(true);
	if(pItem->IsEquipment())
	{
		BindLinkCommand(dynamic_cast<PAUITEXTAREA>(pObj), NULL,
			&CDlgWikiEquipment::ShowSpecficLinkCommand(id, pItem->GetName()));
	}
	else
	{
		BindLinkCommand(dynamic_cast<PAUITEXTAREA>(pObj), NULL,
			&CDlgWikiItem::ShowSpecficLinkCommand(id, pItem->GetName()));
	}

	if(pName)
	{
		float prob = m_filteredDrop[iCandidate].probability * 100.0f;
		ACString strProb;
		if(prob > 0.0f && prob < 0.01f)
			strProb = _AL("<0.01%");
		else
			strProb.Format(_AL("%.2f%%"), prob);
		pName->SetText(strProb);
		pName->Show(true);
	}

	if(iIconIdx >= 0 && iIconIdx < MAX_ICONS && m_pProgress[iIconIdx])
	{
		float prob = m_filteredDrop[iCandidate].probability;
		int val = (int)(prob * 10000.0f);
		if(val < 1)     val = 1;
		if(val > 10000) val = 10000;
		m_pProgress[iIconIdx]->SetProgress(val);
		m_pProgress[iIconIdx]->Show(true);
	}

	delete pItem;
	return true;
}

void CDlgWikiMonsterDrop::OnEndSearch()
{
	int iFirstEmpty = m_ObjIndex;
	a_LogOutput(1, "WikiDrop: OnEndSearch iFirstEmpty=%d", iFirstEmpty);

	CDlgWikiItemListBase::OnEndSearch();

	int iStart = (iFirstEmpty > 0) ? (iFirstEmpty - 1) : 0;
	for(int i = iStart; i < MAX_ICONS; i++)
	{
		if(m_pIcons[i])
		{
			m_pIcons[i]->SetCover(NULL, -1);
			m_pIcons[i]->Show(false);
		}
		if(m_pProgress[i])
		{
			m_pProgress[i]->SetProgress(0);
			m_pProgress[i]->Show(false);
		}
	}

	UpdateStatsDisplay(m_pCachedEssence);
}
